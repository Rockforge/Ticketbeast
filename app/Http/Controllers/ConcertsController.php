<?php

namespace App\Http\Controllers;

use App\Models\Concert;
use Illuminate\Http\Request;

class ConcertsController extends Controller
{
    /**
     * Show the concert instance
     *
     * @param int $id
     *
     * @return View
     */
    public function show($id)
    {
        $concert = Concert::published()->findOrFail($id);

        return view('concerts.show', [
            'concert' => $concert
        ]);
    }
}
