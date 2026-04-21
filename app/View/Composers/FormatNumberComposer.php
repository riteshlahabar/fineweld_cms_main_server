<?php

namespace App\View\Composers;

use App\Traits\FormatNumber;
use Illuminate\View\View;

class FormatNumberComposer
{
    public function compose(View $view)
    {
        $formatter = new class
        {
            use FormatNumber;
        };
        $view->with('formatNumber', $formatter);
    }
}
