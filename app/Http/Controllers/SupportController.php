<?php

namespace App\Http\Controllers;

use App\Support\SupportInfo;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public function show(): Response
    {
        $info = SupportInfo::public();
        abort_unless($info, 404);

        return Inertia::render('support', $info);
    }
}
