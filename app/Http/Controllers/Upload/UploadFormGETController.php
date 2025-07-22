<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class UploadFormGETController extends Controller
{
    /**
     * Handle refreshes.
     */
    public function __invoke()
    {
        //
           return Inertia::render('uploads/PatientData', [
            'patients_validation' => session('patients_validation'),
            'visits_validation' => session('visits_validation'),
            'cross_validation' => session('cross_validation'),
        ]);
    }
}
