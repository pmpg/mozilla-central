/* -*- Mode: C++; tab-width: 8; indent-tabs-mode: nil; c-basic-offset: 4 -*- */
/*
 * The contents of this file are subject to the Netscape Public License
 * Version 1.0 (the "NPL"); you may not use this file except in
 * compliance with the NPL.  You may obtain a copy of the NPL at
 * http://www.mozilla.org/NPL/
 *
 * Software distributed under the NPL is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the NPL
 * for the specific language governing rights and limitations under the
 * NPL.
 *
 * The Initial Developer of this code under the NPL is Netscape
 * Communications Corporation.  Portions created by Netscape are
 * Copyright (C) 1999 Netscape Communications Corporation.  All Rights
 * Reserved.
 */

// XXX fold this (and the .h file) into nsInterfaceInfoManager.cpp?

#include "nsTypelibRecord.h"
#include "nsInterfaceRecord.h"
#include "xpt_struct.h"

nsTypelibRecord::nsTypelibRecord(int size, nsTypelibRecord *in_next,
                                 XPTHeader *in_header, nsIAllocator *allocator)
    : next(in_next),
      header(in_header)
{
    this->interfaceRecords = (nsInterfaceRecord **)
        allocator->Alloc((size + 1) * (sizeof (nsInterfaceRecord *)));
    // XXX how to account for failure in a constructor?  Can we return null?

    // NULL-terminate it.  The rest will get filled in.
    this->interfaceRecords[size] = NULL;
}

void
nsTypelibRecord::Destroy(nsIAllocator* aAllocator)
{
    XPT_FreeHeader(header);
    header = nsnull;
    aAllocator->Free(interfaceRecords);
    interfaceRecords = nsnull;
}

void nsTypelibRecord::DestroyList(nsTypelibRecord* aList,
                                  nsIAllocator* aAllocator)
{
    while (aList) {
        nsTypelibRecord* next = aList->next;
        aList->Destroy(aAllocator);
        delete aList;
        aList = next;
    }
}
