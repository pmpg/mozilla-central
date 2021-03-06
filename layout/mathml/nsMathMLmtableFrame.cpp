/* -*- Mode: C++; tab-width: 2; indent-tabs-mode: nil; c-basic-offset: 2 -*- */
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

#include "nsCOMPtr.h"
#include "nsFrame.h"
#include "nsBlockFrame.h"
#include "nsPresContext.h"
#include "nsStyleContext.h"
#include "nsStyleConsts.h"
#include "nsTableRowFrame.h"
#include "nsINameSpaceManager.h"
#include "nsRenderingContext.h"

#include "nsTArray.h"
#include "nsCSSFrameConstructor.h"
#include "nsTableOuterFrame.h"
#include "nsTableFrame.h"
#include "nsTableCellFrame.h"
#include "celldata.h"

#include "nsMathMLmtableFrame.h"
#include <algorithm>

using namespace mozilla;

//
// <mtable> -- table or matrix - implementation
//

// helper function to perform an in-place split of a space-delimited string,
// and return an array of pointers for the beginning of each segment, i.e.,
// aOffset[0] is the first string, aOffset[1] is the second string, etc.
// Used to parse attributes like columnalign='left right', rowalign='top bottom'
static void
SplitString(nsString&             aString, // [IN/OUT]
            nsTArray<PRUnichar*>& aOffset) // [OUT]
{
  static const PRUnichar kNullCh = PRUnichar('\0');

  aString.Append(kNullCh);  // put an extra null at the end

  PRUnichar* start = aString.BeginWriting();
  PRUnichar* end   = start;

  while (kNullCh != *start) {
    while ((kNullCh != *start) && nsCRT::IsAsciiSpace(*start)) {  // skip leading space
      start++;
    }
    end = start;

    while ((kNullCh != *end) && (false == nsCRT::IsAsciiSpace(*end))) { // look for space or end
      end++;
    }
    *end = kNullCh; // end string here

    if (start < end) {
      aOffset.AppendElement(start); // record the beginning of this segment
    }

    start = ++end;
  }
}

struct nsValueList
{
  nsString             mData;
  nsTArray<PRUnichar*> mArray;

  nsValueList(nsString& aData) {
    mData.Assign(aData);
    SplitString(mData, mArray);
  }
};

// Each rowalign='top bottom' or columnalign='left right center' (from
// <mtable> or <mtr>) is split once (lazily) into a nsValueList which is
// stored in the property table. Row/Cell frames query the property table
// to see what values apply to them.

// XXX See bug 69409 - MathML attributes are not mapped to style.

static void
DestroyValueList(void* aPropertyValue)
{
  delete static_cast<nsValueList*>(aPropertyValue);
}

NS_DECLARE_FRAME_PROPERTY(RowAlignProperty, DestroyValueList)
NS_DECLARE_FRAME_PROPERTY(RowLinesProperty, DestroyValueList)
NS_DECLARE_FRAME_PROPERTY(ColumnAlignProperty, DestroyValueList)
NS_DECLARE_FRAME_PROPERTY(ColumnLinesProperty, DestroyValueList)

static const FramePropertyDescriptor*
AttributeToProperty(nsIAtom* aAttribute)
{
  if (aAttribute == nsGkAtoms::rowalign_)
    return RowAlignProperty();
  if (aAttribute == nsGkAtoms::rowlines_)
    return RowLinesProperty();
  if (aAttribute == nsGkAtoms::columnalign_)
    return ColumnAlignProperty();
  NS_ASSERTION(aAttribute == nsGkAtoms::columnlines_, "Invalid attribute");
  return ColumnLinesProperty();
}

static PRUnichar*
GetValueAt(nsIFrame*                      aTableOrRowFrame,
           const FramePropertyDescriptor* aProperty,
           nsIAtom*                       aAttribute,
           int32_t                        aRowOrColIndex)
{
  FrameProperties props = aTableOrRowFrame->Properties();
  nsValueList* valueList = static_cast<nsValueList*>(props.Get(aProperty));
  if (!valueList) {
    // The property isn't there yet, so set it
    nsAutoString values;
    aTableOrRowFrame->GetContent()->GetAttr(kNameSpaceID_None, aAttribute, values);
    if (!values.IsEmpty())
      valueList = new nsValueList(values);
    if (!valueList || !valueList->mArray.Length()) {
      delete valueList; // ok either way, delete is null safe
      return nullptr;
    }
    props.Set(aProperty, valueList);
  }
  int32_t count = valueList->mArray.Length();
  return (aRowOrColIndex < count)
         ? valueList->mArray[aRowOrColIndex]
         : valueList->mArray[count-1];
}

#ifdef DEBUG
static bool
IsTable(uint8_t aDisplay)
{
  if ((aDisplay == NS_STYLE_DISPLAY_TABLE) ||
      (aDisplay == NS_STYLE_DISPLAY_INLINE_TABLE))
    return true;
  return false;
}

#define DEBUG_VERIFY_THAT_FRAME_IS(_frame, _expected) \
  NS_ASSERTION(NS_STYLE_DISPLAY_##_expected == _frame->StyleDisplay()->mDisplay, "internal error");
#define DEBUG_VERIFY_THAT_FRAME_IS_TABLE(_frame) \
  NS_ASSERTION(IsTable(_frame->StyleDisplay()->mDisplay), "internal error");
#else
#define DEBUG_VERIFY_THAT_FRAME_IS(_frame, _expected)
#define DEBUG_VERIFY_THAT_FRAME_IS_TABLE(_frame)
#endif

// map attributes that depend on the index of the row:
// rowalign, rowlines, XXX need rowspacing too
static void
MapRowAttributesIntoCSS(nsIFrame* aTableFrame,
                        nsIFrame* aRowFrame)
{
  DEBUG_VERIFY_THAT_FRAME_IS_TABLE(aTableFrame);
  DEBUG_VERIFY_THAT_FRAME_IS(aRowFrame, TABLE_ROW);
  int32_t rowIndex = ((nsTableRowFrame*)aRowFrame)->GetRowIndex();
  nsIContent* rowContent = aRowFrame->GetContent();
  PRUnichar* attr;

  // see if the rowalign attribute is not already set
  if (!rowContent->HasAttr(kNameSpaceID_None, nsGkAtoms::rowalign_) &&
      !rowContent->HasAttr(kNameSpaceID_None, nsGkAtoms::_moz_math_rowalign_)) {
    // see if the rowalign attribute was specified on the table
    attr = GetValueAt(aTableFrame, RowAlignProperty(),
                      nsGkAtoms::rowalign_, rowIndex);
    if (attr) {
      // set our special _moz attribute on the row without notifying a reflow
      rowContent->SetAttr(kNameSpaceID_None, nsGkAtoms::_moz_math_rowalign_,
                          nsDependentString(attr), false);
    }
  }

  // if we are not on the first row, see if |rowlines| was specified on the table.
  // Note that we pass 'rowIndex-1' because the CSS rule in mathml.css is associated
  // to 'border-top', and it is as if we draw the line on behalf of the previous cell.
  // This way of doing so allows us to handle selective lines, [row]\hline[row][row]',
  // and cases of spanning cells without further complications.
  if (rowIndex > 0 &&
      !rowContent->HasAttr(kNameSpaceID_None, nsGkAtoms::_moz_math_rowline_)) {
    attr = GetValueAt(aTableFrame, RowLinesProperty(),
                      nsGkAtoms::rowlines_, rowIndex-1);
    if (attr) {
      // set our special _moz attribute on the row without notifying a reflow
      rowContent->SetAttr(kNameSpaceID_None, nsGkAtoms::_moz_math_rowline_,
                          nsDependentString(attr), false);
    }
  }
}

// map attributes that depend on the index of the column:
// columnalign, columnlines, XXX need columnwidth and columnspacing too
static void
MapColAttributesIntoCSS(nsIFrame* aTableFrame,
                        nsIFrame* aRowFrame,
                        nsIFrame* aCellFrame)
{
  DEBUG_VERIFY_THAT_FRAME_IS_TABLE(aTableFrame);
  DEBUG_VERIFY_THAT_FRAME_IS(aRowFrame, TABLE_ROW);
  DEBUG_VERIFY_THAT_FRAME_IS(aCellFrame, TABLE_CELL);
  int32_t rowIndex, colIndex;
  ((nsTableCellFrame*)aCellFrame)->GetCellIndexes(rowIndex, colIndex);
  nsIContent* cellContent = aCellFrame->GetContent();
  PRUnichar* attr;

  // see if the columnalign attribute is not already set
  if (!cellContent->HasAttr(kNameSpaceID_None, nsGkAtoms::columnalign_) &&
      !cellContent->HasAttr(kNameSpaceID_None,
                            nsGkAtoms::_moz_math_columnalign_)) {
    // see if the columnalign attribute was specified on the row
    attr = GetValueAt(aRowFrame, ColumnAlignProperty(),
                      nsGkAtoms::columnalign_, colIndex);
    if (!attr) {
      // see if the columnalign attribute was specified on the table
      attr = GetValueAt(aTableFrame, ColumnAlignProperty(),
                        nsGkAtoms::columnalign_, colIndex);
    }
    if (attr) {
      // set our special _moz attribute without notifying a reflow
      cellContent->SetAttr(kNameSpaceID_None, nsGkAtoms::_moz_math_columnalign_,
                           nsDependentString(attr), false);
    }
  }

  // if we are not on the first column, see if |columnlines| was specified on
  // the table. Note that we pass 'colIndex-1' because the CSS rule in mathml.css
  // is associated to 'border-left', and it is as if we draw the line on behalf
  // of the previous cell. This way of doing so allows us to handle selective lines,
  // e.g., 'r|cl', and cases of spanning cells without further complications.
  if (colIndex > 0 &&
      !cellContent->HasAttr(kNameSpaceID_None,
                            nsGkAtoms::_moz_math_columnline_)) {
    attr = GetValueAt(aTableFrame, ColumnLinesProperty(),
                      nsGkAtoms::columnlines_, colIndex-1);
    if (attr) {
      // set our special _moz attribute without notifying a reflow
      cellContent->SetAttr(kNameSpaceID_None, nsGkAtoms::_moz_math_columnline_,
                           nsDependentString(attr), false);
    }
  }
}

// map all attribues within a table -- requires the indices of rows and cells.
// so it can only happen after they are made ready by the table base class.
static void
MapAllAttributesIntoCSS(nsIFrame* aTableFrame)
{
  // mtable is simple and only has one (pseudo) row-group
  nsIFrame* rgFrame = aTableFrame->GetFirstPrincipalChild();
  if (!rgFrame || rgFrame->GetType() != nsGkAtoms::tableRowGroupFrame)
    return;

  nsIFrame* rowFrame = rgFrame->GetFirstPrincipalChild();
  for ( ; rowFrame; rowFrame = rowFrame->GetNextSibling()) {
    DEBUG_VERIFY_THAT_FRAME_IS(rowFrame, TABLE_ROW);
    if (rowFrame->GetType() == nsGkAtoms::tableRowFrame) {
      MapRowAttributesIntoCSS(aTableFrame, rowFrame);
      nsIFrame* cellFrame = rowFrame->GetFirstPrincipalChild();
      for ( ; cellFrame; cellFrame = cellFrame->GetNextSibling()) {
        DEBUG_VERIFY_THAT_FRAME_IS(cellFrame, TABLE_CELL);
        if (IS_TABLE_CELL(cellFrame->GetType())) {
          MapColAttributesIntoCSS(aTableFrame, rowFrame, cellFrame);
        }
      }
    }
  }
}

// the align attribute of mtable can have a row number which indicates
// from where to anchor the table, e.g., top 5 means anchor the table at
// the top of the 5th row, axis -1 means anchor the table on the axis of
// the last row

// The REC says that the syntax is 
// '\s*(top|bottom|center|baseline|axis)(\s+-?[0-9]+)?\s*' 
// the parsing could have been simpler with that syntax
// but for backward compatibility we make optional 
// the whitespaces between the alignment name and the row number

enum eAlign {
  eAlign_top,
  eAlign_bottom,
  eAlign_center,
  eAlign_baseline,
  eAlign_axis
};

static void
ParseAlignAttribute(nsString& aValue, eAlign& aAlign, int32_t& aRowIndex)
{
  // by default, the table is centered about the axis
  aRowIndex = 0;
  aAlign = eAlign_axis;
  int32_t len = 0;

  // we only have to remove the leading spaces because 
  // ToInteger ignores the whitespaces around the number
  aValue.CompressWhitespace(true, false);

  if (0 == aValue.Find("top")) {
    len = 3; // 3 is the length of 'top'
    aAlign = eAlign_top;
  }
  else if (0 == aValue.Find("bottom")) {
    len = 6; // 6 is the length of 'bottom'
    aAlign = eAlign_bottom;
  }
  else if (0 == aValue.Find("center")) {
    len = 6; // 6 is the length of 'center'
    aAlign = eAlign_center;
  }
  else if (0 == aValue.Find("baseline")) {
    len = 8; // 8 is the length of 'baseline'
    aAlign = eAlign_baseline;
  }
  else if (0 == aValue.Find("axis")) {
    len = 4; // 4 is the length of 'axis'
    aAlign = eAlign_axis;
  }
  if (len) {
    nsresult error;
    aValue.Cut(0, len); // aValue is not a const here
    aRowIndex = aValue.ToInteger(&error);
    if (NS_FAILED(error))
      aRowIndex = 0;
  }
}

#ifdef DEBUG_rbs_off
// call ListMathMLTree(mParent) to get the big picture
static void
ListMathMLTree(nsIFrame* atLeast)
{
  // climb up to <math> or <body> if <math> isn't there
  nsIFrame* f = atLeast;
  for ( ; f; f = f->GetParent()) {
    nsIContent* c = f->GetContent();
    if (!c || c->Tag() == nsGkAtoms::math || c->Tag() == nsGkAtoms::body)
      break;
  }
  if (!f) f = atLeast;
  f->List(stdout, 0);
}
#endif

// --------
// implementation of nsMathMLmtableOuterFrame

NS_QUERYFRAME_HEAD(nsMathMLmtableOuterFrame)
  NS_QUERYFRAME_ENTRY(nsIMathMLFrame)
NS_QUERYFRAME_TAIL_INHERITING(nsTableOuterFrame)

nsIFrame*
NS_NewMathMLmtableOuterFrame (nsIPresShell* aPresShell, nsStyleContext* aContext)
{
  return new (aPresShell) nsMathMLmtableOuterFrame(aContext);
}

NS_IMPL_FRAMEARENA_HELPERS(nsMathMLmtableOuterFrame)
 
nsMathMLmtableOuterFrame::~nsMathMLmtableOuterFrame()
{
}

NS_IMETHODIMP
nsMathMLmtableOuterFrame::InheritAutomaticData(nsIFrame* aParent)
{
  // XXX the REC says that by default, displaystyle=false in <mtable>

  // let the base class inherit the displaystyle from our parent
  nsMathMLFrame::InheritAutomaticData(aParent);

  // see if the displaystyle attribute is there and let it override what we inherited
  if (mContent->Tag() == nsGkAtoms::mtable_)
    nsMathMLFrame::FindAttrDisplaystyle(mContent, mPresentationData);

  return NS_OK;
}

// displaystyle is special in mtable...
// Since UpdatePresentation() and UpdatePresentationDataFromChildAt() can be called
// by a parent, ensure that the displaystyle attribute of mtable takes precedence
NS_IMETHODIMP
nsMathMLmtableOuterFrame::UpdatePresentationData(uint32_t aFlagsValues,
                                                 uint32_t aWhichFlags)
{
  if (NS_MATHML_HAS_EXPLICIT_DISPLAYSTYLE(mPresentationData.flags)) {
    // our current state takes precedence, disallow updating the displastyle
    aWhichFlags &= ~NS_MATHML_DISPLAYSTYLE;
    aFlagsValues &= ~NS_MATHML_DISPLAYSTYLE;
  }

  return nsMathMLFrame::UpdatePresentationData(aFlagsValues, aWhichFlags);
}

NS_IMETHODIMP
nsMathMLmtableOuterFrame::UpdatePresentationDataFromChildAt(int32_t  aFirstIndex,
                                                            int32_t  aLastIndex,
                                                            uint32_t aFlagsValues,
                                                            uint32_t aWhichFlags)
{
  if (NS_MATHML_HAS_EXPLICIT_DISPLAYSTYLE(mPresentationData.flags)) {
    // our current state takes precedence, disallow updating the displastyle
    aWhichFlags &= ~NS_MATHML_DISPLAYSTYLE;
    aFlagsValues &= ~NS_MATHML_DISPLAYSTYLE;
  }

  nsMathMLContainerFrame::PropagatePresentationDataFromChildAt(this,
    aFirstIndex, aLastIndex, aFlagsValues, aWhichFlags);

  return NS_OK; 
}

NS_IMETHODIMP
nsMathMLmtableOuterFrame::AttributeChanged(int32_t  aNameSpaceID,
                                           nsIAtom* aAttribute,
                                           int32_t  aModType)
{
  // Attributes specific to <mtable>:
  // frame         : in mathml.css
  // framespacing  : not yet supported 
  // groupalign    : not yet supported
  // equalrows     : not yet supported 
  // equalcolumns  : not yet supported 
  // displaystyle  : here 
  // align         : in reflow 
  // rowalign      : here
  // rowlines      : here 
  // rowspacing    : not yet supported 
  // columnalign   : here 
  // columnlines   : here 
  // columnspacing : not yet supported 

  // mtable is simple and only has one (pseudo) row-group inside our inner-table
  nsIFrame* tableFrame = mFrames.FirstChild();
  NS_ASSERTION(tableFrame && tableFrame->GetType() == nsGkAtoms::tableFrame,
               "should always have an inner table frame");
  nsIFrame* rgFrame = tableFrame->GetFirstPrincipalChild();
  if (!rgFrame || rgFrame->GetType() != nsGkAtoms::tableRowGroupFrame)
    return NS_OK;

  // align - just need to issue a dirty (resize) reflow command
  if (aAttribute == nsGkAtoms::align) {
    PresContext()->PresShell()->
      FrameNeedsReflow(this, nsIPresShell::eResize, NS_FRAME_IS_DIRTY);
    return NS_OK;
  }

  // displaystyle - may seem innocuous, but it is actually very harsh --
  // like changing an unit. Blow away and recompute all our automatic
  // presentational data, and issue a style-changed reflow request
  if (aAttribute == nsGkAtoms::displaystyle_) {
    nsMathMLContainerFrame::RebuildAutomaticDataForChildren(mParent);
    // Need to reflow the parent, not us, because this can actually
    // affect siblings.
    PresContext()->PresShell()->
      FrameNeedsReflow(mParent, nsIPresShell::eStyleChange, NS_FRAME_IS_DIRTY);
    return NS_OK;
  }

  // ...and the other attributes affect rows or columns in one way or another
  nsIAtom* MOZrowAtom = nullptr;
  nsIAtom* MOZcolAtom = nullptr;
  if (aAttribute == nsGkAtoms::rowalign_)
    MOZrowAtom = nsGkAtoms::_moz_math_rowalign_;
  else if (aAttribute == nsGkAtoms::rowlines_)
    MOZrowAtom = nsGkAtoms::_moz_math_rowline_;
  else if (aAttribute == nsGkAtoms::columnalign_)
    MOZcolAtom = nsGkAtoms::_moz_math_columnalign_;
  else if (aAttribute == nsGkAtoms::columnlines_)
    MOZcolAtom = nsGkAtoms::_moz_math_columnline_;

  if (!MOZrowAtom && !MOZcolAtom)
    return NS_OK;

  nsPresContext* presContext = tableFrame->PresContext();
  // clear any cached nsValueList for this table
  presContext->PropertyTable()->
    Delete(tableFrame, AttributeToProperty(aAttribute));

  // unset any _moz attribute that we may have set earlier, and re-sync
  nsIFrame* rowFrame = rgFrame->GetFirstPrincipalChild();
  for ( ; rowFrame; rowFrame = rowFrame->GetNextSibling()) {
    if (rowFrame->GetType() == nsGkAtoms::tableRowFrame) {
      if (MOZrowAtom) { // let rows do the work
        rowFrame->GetContent()->UnsetAttr(kNameSpaceID_None, MOZrowAtom, false);
        MapRowAttributesIntoCSS(tableFrame, rowFrame);    
      } else { // let cells do the work
        nsIFrame* cellFrame = rowFrame->GetFirstPrincipalChild();
        for ( ; cellFrame; cellFrame = cellFrame->GetNextSibling()) {
          if (IS_TABLE_CELL(cellFrame->GetType())) {
            cellFrame->GetContent()->UnsetAttr(kNameSpaceID_None, MOZcolAtom, false);
            MapColAttributesIntoCSS(tableFrame, rowFrame, cellFrame);
          }
        }
      }
    }
  }

  // Explicitly request a re-resolve and reflow in our subtree to pick up any changes
  presContext->RestyleManager()->
    PostRestyleEvent(mContent->AsElement(), eRestyle_Subtree,
                     nsChangeHint_AllReflowHints);

  return NS_OK;
}

nsIFrame*
nsMathMLmtableOuterFrame::GetRowFrameAt(nsPresContext* aPresContext,
                                        int32_t         aRowIndex)
{
  int32_t rowCount = GetRowCount();

  // Negative indices mean to find upwards from the end.
  if (aRowIndex < 0) {
    aRowIndex = rowCount + aRowIndex;
  } else {
    // aRowIndex is 1-based, so convert it to a 0-based index
    --aRowIndex;
  }

  // if our inner table says that the index is valid, find the row now
  if (0 <= aRowIndex && aRowIndex <= rowCount) {
    nsIFrame* tableFrame = mFrames.FirstChild();
    NS_ASSERTION(tableFrame && tableFrame->GetType() == nsGkAtoms::tableFrame,
                 "should always have an inner table frame");
    nsIFrame* rgFrame = tableFrame->GetFirstPrincipalChild();
    if (!rgFrame || rgFrame->GetType() != nsGkAtoms::tableRowGroupFrame)
      return nullptr;
    nsTableIterator rowIter(*rgFrame);
    nsIFrame* rowFrame = rowIter.First();
    for ( ; rowFrame; rowFrame = rowIter.Next()) {
      if (aRowIndex == 0) {
        DEBUG_VERIFY_THAT_FRAME_IS(rowFrame, TABLE_ROW);
        if (rowFrame->GetType() != nsGkAtoms::tableRowFrame)
          return nullptr;

        return rowFrame;
      }
      --aRowIndex;
    }
  }
  return nullptr;
}

NS_IMETHODIMP
nsMathMLmtableOuterFrame::Reflow(nsPresContext*          aPresContext,
                                 nsHTMLReflowMetrics&     aDesiredSize,
                                 const nsHTMLReflowState& aReflowState,
                                 nsReflowStatus&          aStatus)
{
  nsresult rv;
  nsAutoString value;
  // we want to return a table that is anchored according to the align attribute

  rv = nsTableOuterFrame::Reflow(aPresContext, aDesiredSize, aReflowState,
                                 aStatus);
  NS_ASSERTION(aDesiredSize.height >= 0, "illegal height for mtable");
  NS_ASSERTION(aDesiredSize.width >= 0, "illegal width for mtable");

  // see if the user has set the align attribute on the <mtable>
  // XXX should we also check <mstyle> ?
  int32_t rowIndex = 0;
  eAlign tableAlign = eAlign_axis;
  GetAttribute(mContent, nullptr, nsGkAtoms::align, value);
  if (!value.IsEmpty()) {
    ParseAlignAttribute(value, tableAlign, rowIndex);
  }

  // adjustments if there is a specified row from where to anchor the table
  // (conceptually: when there is no row of reference, picture the table as if
  // it is wrapped in a single big fictional row at dy = 0, this way of
  // doing so allows us to have a single code path for all cases).
  nscoord dy = 0;
  nscoord height = aDesiredSize.height;
  nsIFrame* rowFrame = nullptr;
  if (rowIndex) {
    rowFrame = GetRowFrameAt(aPresContext, rowIndex);
    if (rowFrame) {
      // translate the coordinates to be relative to us
      nsIFrame* frame = rowFrame;
      height = frame->GetSize().height;
      do {
        dy += frame->GetPosition().y;
        frame = frame->GetParent();
      } while (frame != this);
    }
  }
  switch (tableAlign) {
    case eAlign_top:
      aDesiredSize.ascent = dy;
      break;
    case eAlign_bottom:
      aDesiredSize.ascent = dy + height;
      break;
    case eAlign_center:
      aDesiredSize.ascent = dy + height/2;
      break;
    case eAlign_baseline:
      if (rowFrame) {
        // anchor the table on the baseline of the row of reference
        nscoord rowAscent = ((nsTableRowFrame*)rowFrame)->GetMaxCellAscent();
        if (rowAscent) { // the row has at least one cell with 'vertical-align: baseline'
          aDesiredSize.ascent = dy + rowAscent;
          break;
        }
      }
      // in other situations, fallback to center
      aDesiredSize.ascent = dy + height/2;
      break;
    case eAlign_axis:
    default: {
      // XXX should instead use style data from the row of reference here ?
      nsRefPtr<nsFontMetrics> fm;
      nsLayoutUtils::GetFontMetricsForFrame(this, getter_AddRefs(fm));
      aReflowState.rendContext->SetFont(fm);
      nscoord axisHeight;
      GetAxisHeight(*aReflowState.rendContext,
                    aReflowState.rendContext->FontMetrics(),
                    axisHeight);
      if (rowFrame) {
        // anchor the table on the axis of the row of reference
        // XXX fallback to baseline because it is a hard problem
        // XXX need to fetch the axis of the row; would need rowalign=axis to work better
        nscoord rowAscent = ((nsTableRowFrame*)rowFrame)->GetMaxCellAscent();
        if (rowAscent) { // the row has at least one cell with 'vertical-align: baseline'
          aDesiredSize.ascent = dy + rowAscent;
          break;
        }
      }
      // in other situations, fallback to using half of the height
      aDesiredSize.ascent = dy + height/2 + axisHeight;
    }
  }

  mReference.x = 0;
  mReference.y = aDesiredSize.ascent;

  // just make-up a bounding metrics
  mBoundingMetrics = nsBoundingMetrics();
  mBoundingMetrics.ascent = aDesiredSize.ascent;
  mBoundingMetrics.descent = aDesiredSize.height - aDesiredSize.ascent;
  mBoundingMetrics.width = aDesiredSize.width;
  mBoundingMetrics.leftBearing = 0;
  mBoundingMetrics.rightBearing = aDesiredSize.width;

  aDesiredSize.mBoundingMetrics = mBoundingMetrics;
  NS_FRAME_SET_TRUNCATION(aStatus, aReflowState, aDesiredSize);

  return rv;
}

nsIFrame*
NS_NewMathMLmtableFrame(nsIPresShell* aPresShell, nsStyleContext* aContext)
{
  return new (aPresShell) nsMathMLmtableFrame(aContext);
}

NS_IMPL_FRAMEARENA_HELPERS(nsMathMLmtableFrame)

nsMathMLmtableFrame::~nsMathMLmtableFrame()
{
}

NS_IMETHODIMP
nsMathMLmtableFrame::SetInitialChildList(ChildListID  aListID,
                                         nsFrameList& aChildList)
{
  nsresult rv = nsTableFrame::SetInitialChildList(aListID, aChildList);
  if (NS_FAILED(rv)) return rv;
  MapAllAttributesIntoCSS(this);
  return rv;
}

void
nsMathMLmtableFrame::RestyleTable()
{
  // re-sync MathML specific style data that may have changed
  MapAllAttributesIntoCSS(this);

  // Explicitly request a re-resolve and reflow in our subtree to pick up any changes
  PresContext()->RestyleManager()->
    PostRestyleEvent(mContent->AsElement(), eRestyle_Subtree,
                     nsChangeHint_AllReflowHints);
}

// --------
// implementation of nsMathMLmtrFrame

nsIFrame*
NS_NewMathMLmtrFrame(nsIPresShell* aPresShell, nsStyleContext* aContext)
{
  return new (aPresShell) nsMathMLmtrFrame(aContext);
}

NS_IMPL_FRAMEARENA_HELPERS(nsMathMLmtrFrame)

nsMathMLmtrFrame::~nsMathMLmtrFrame()
{
}

NS_IMETHODIMP
nsMathMLmtrFrame::AttributeChanged(int32_t  aNameSpaceID,
                                   nsIAtom* aAttribute,
                                   int32_t  aModType)
{
  // Attributes specific to <mtr>:
  // groupalign  : Not yet supported.
  // rowalign    : Fully specified in mathml.css, and so HasAttributeDependentStyle() will
  //               pick it up and RestyleManager will issue a PostRestyleEvent().
  // columnalign : Need an explicit re-style call.

  if (aAttribute == nsGkAtoms::rowalign_) {
    // unset any _moz attribute that we may have set earlier, and re-sync
    mContent->UnsetAttr(kNameSpaceID_None, nsGkAtoms::_moz_math_rowalign_,
                        false);
    MapRowAttributesIntoCSS(nsTableFrame::GetTableFrame(this), this);
    // That's all - see comment above.
    return NS_OK;
  }

  if (aAttribute != nsGkAtoms::columnalign_)
    return NS_OK;

  nsPresContext* presContext = PresContext();
  // Clear any cached columnalign's nsValueList for this row
  presContext->PropertyTable()->Delete(this, AttributeToProperty(aAttribute));

  // Clear any internal _moz attribute that we may have set earlier
  // in our cells and re-sync their columnalign attribute
  nsTableFrame* tableFrame = nsTableFrame::GetTableFrame(this);
  nsIFrame* cellFrame = GetFirstPrincipalChild();
  for ( ; cellFrame; cellFrame = cellFrame->GetNextSibling()) {
    if (IS_TABLE_CELL(cellFrame->GetType())) {
      cellFrame->GetContent()->
        UnsetAttr(kNameSpaceID_None, nsGkAtoms::_moz_math_columnalign_,
                  false);
      MapColAttributesIntoCSS(tableFrame, this, cellFrame);
    }
  }

  // Explicitly request a re-resolve and reflow in our subtree to pick up any changes
  presContext->RestyleManager()->
    PostRestyleEvent(mContent->AsElement(), eRestyle_Subtree,
                     nsChangeHint_AllReflowHints);

  return NS_OK;
}

// --------
// implementation of nsMathMLmtdFrame

nsIFrame*
NS_NewMathMLmtdFrame(nsIPresShell* aPresShell, nsStyleContext* aContext)
{
  return new (aPresShell) nsMathMLmtdFrame(aContext);
}

NS_IMPL_FRAMEARENA_HELPERS(nsMathMLmtdFrame)

nsMathMLmtdFrame::~nsMathMLmtdFrame()
{
}

int32_t
nsMathMLmtdFrame::GetRowSpan()
{
  int32_t rowspan = 1;

  // Don't look at the content's rowspan if we're not an mtd or a pseudo cell.
  if ((mContent->Tag() == nsGkAtoms::mtd_) && !StyleContext()->GetPseudo()) {
    nsAutoString value;
    mContent->GetAttr(kNameSpaceID_None, nsGkAtoms::rowspan, value);
    if (!value.IsEmpty()) {
      nsresult error;
      rowspan = value.ToInteger(&error);
      if (NS_FAILED(error) || rowspan < 0)
        rowspan = 1;
      rowspan = std::min(rowspan, MAX_ROWSPAN);
    }
  }
  return rowspan;
}

int32_t
nsMathMLmtdFrame::GetColSpan()
{
  int32_t colspan = 1;

  // Don't look at the content's colspan if we're not an mtd or a pseudo cell.
  if ((mContent->Tag() == nsGkAtoms::mtd_) && !StyleContext()->GetPseudo()) {
    nsAutoString value;
    mContent->GetAttr(kNameSpaceID_None, nsGkAtoms::columnspan_, value);
    if (!value.IsEmpty()) {
      nsresult error;
      colspan = value.ToInteger(&error);
      if (NS_FAILED(error) || colspan < 0 || colspan > MAX_COLSPAN)
        colspan = 1;
    }
  }
  return colspan;
}

NS_IMETHODIMP
nsMathMLmtdFrame::AttributeChanged(int32_t  aNameSpaceID,
                                   nsIAtom* aAttribute,
                                   int32_t  aModType)
{
  // Attributes specific to <mtd>:
  // groupalign  : Not yet supported
  // rowalign    : in mathml.css
  // columnalign : here
  // rowspan     : here
  // columnspan  : here

  if (aAttribute == nsGkAtoms::columnalign_) {
    // unset any _moz attribute that we may have set earlier, and re-sync
    mContent->UnsetAttr(kNameSpaceID_None, nsGkAtoms::_moz_math_columnalign_,
                        false);
    MapColAttributesIntoCSS(nsTableFrame::GetTableFrame(this), mParent, this);
    return NS_OK;
  }

  if (aAttribute == nsGkAtoms::rowspan ||
      aAttribute == nsGkAtoms::columnspan_) {
    // use the naming expected by the base class 
    if (aAttribute == nsGkAtoms::columnspan_)
      aAttribute = nsGkAtoms::colspan;
    return nsTableCellFrame::AttributeChanged(aNameSpaceID, aAttribute, aModType);
  }

  return NS_OK;
}

// --------
// implementation of nsMathMLmtdInnerFrame

NS_QUERYFRAME_HEAD(nsMathMLmtdInnerFrame)
  NS_QUERYFRAME_ENTRY(nsIMathMLFrame)
NS_QUERYFRAME_TAIL_INHERITING(nsBlockFrame)

nsIFrame*
NS_NewMathMLmtdInnerFrame(nsIPresShell* aPresShell, nsStyleContext* aContext)
{
  return new (aPresShell) nsMathMLmtdInnerFrame(aContext);
}

NS_IMPL_FRAMEARENA_HELPERS(nsMathMLmtdInnerFrame)

nsMathMLmtdInnerFrame::~nsMathMLmtdInnerFrame()
{
}

NS_IMETHODIMP
nsMathMLmtdInnerFrame::Reflow(nsPresContext*          aPresContext,
                              nsHTMLReflowMetrics&     aDesiredSize,
                              const nsHTMLReflowState& aReflowState,
                              nsReflowStatus&          aStatus)
{
  // Let the base class do the reflow
  nsresult rv = nsBlockFrame::Reflow(aPresContext, aDesiredSize, aReflowState, aStatus);

  // more about <maligngroup/> and <malignmark/> later
  // ...
  return rv;
}
