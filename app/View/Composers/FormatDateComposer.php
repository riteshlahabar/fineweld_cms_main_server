<?php

namespace App\View\Composers;

use App\Traits\FormatsDateInputs;
use Illuminate\View\View;

class FormatDateComposer
{
    public function compose(View $view)
    {
        $formatter = new class
        {
            use FormatsDateInputs;
        };
        $view->with('formatDate', $formatter);
    }
}
