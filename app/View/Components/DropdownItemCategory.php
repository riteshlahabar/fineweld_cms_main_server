<?php

namespace App\View\Components;

use App\Models\Items\ItemCategory;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DropdownItemCategory extends Component
{
    /**
     * Categories array
     *
     * @var array
     */
    public $categories;

    /**
     * Selected option
     *
     * @var string
     */
    public $selected;

    /**
     * Multiple Selection box
     *
     * @return bool
     * */
    public $isMultiple;

    /**
     * Show Select Option All
     *
     * @var bool
     */
    public $showSelectOptionAll;

    /**
     * Create a new component instance.
     */
    public function __construct($selected = null, $isMultiple = false, $showSelectOptionAll = false)
    {
        $this->categories = ItemCategory::select('id', 'name')->get();
        $this->selected = $selected;
        $this->isMultiple = $isMultiple;
        $this->showSelectOptionAll = $showSelectOptionAll;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dropdown-item-category');
    }
}
