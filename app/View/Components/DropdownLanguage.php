<?php

namespace App\View\Components;

use App\Models\Language;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DropdownLanguage extends Component
{
    /**
     * Language array
     *
     * @var array
     */
    public $languages;

    /**
     * Selected option
     *
     * @var string
     */
    public $selected;

    /**
     * Create a new component instance.
     */
    public function __construct($selected = null)
    {
        $this->languages = Language::whereStatus(1)
            ->select('id', 'name', 'emoji')
            ->get();
        $this->selected = $selected;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dropdown-language');
    }
}
