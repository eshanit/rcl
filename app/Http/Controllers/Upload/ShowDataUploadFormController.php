<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ShowDataUploadFormController extends Controller
{
    /**
     * Show form for data uploading
     */
    public function __invoke()
    {
        //
        return Inertia::render('uploads/PatientData');
    }
}
