<?php

namespace App\Http\Controllers;

use App\Actions\Web\Landing\GetLandingDataAction;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function index(GetLandingDataAction $action): View
    {
        $landingData = $action->execute();
        return view('landing.index', ['landingData' => $landingData]);
    }
}
