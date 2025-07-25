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
        return Inertia::render('uploads/PatientData', [
            // ,
            'validationResult' => [
                'patients_validation' => session('patients_validation'),
                'visits_validation' => session('visits_validation'),
                'cross_validation' => session('cross_validation'),
            ],
        ]);
    }
}
