<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\AdditionalDocument;
use Illuminate\Http\Request;

class InvoiceDocumentController extends Controller
{
    /**
     * Associate a document with an invoice
     */
    public function associate(Invoice $invoice, AdditionalDocument $document)
    {
        // Only associate if the document is not already associated with another invoice
        if ($document->invoice_id === null) {
            $document->invoice_id = $invoice->id;
            $document->save();
            
            // Redirect back with a success message
            return redirect()->back()->with('success', 'Document associated successfully.');
        }
        
        // Redirect back with an error message
        return redirect()->back()->with('error', 'Document is already associated with another invoice.');
    }
    
    /**
     * Dissociate a document from an invoice
     */
    public function dissociate(Invoice $invoice, AdditionalDocument $document)
    {
        // Only dissociate if the document is associated with this invoice
        if ($document->invoice_id === $invoice->id) {
            $document->invoice_id = null;
            $document->save();
            
            // Redirect back with a success message
            return redirect()->back()->with('success', 'Document dissociated successfully.');
        }
        
        // Redirect back with an error message
        return redirect()->back()->with('error', 'Document is not associated with this invoice.');
    }
} 