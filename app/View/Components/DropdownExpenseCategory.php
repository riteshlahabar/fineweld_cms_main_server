<?php

namespace App\View\Components;

use App\Models\Expenses\ExpenseCategory;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DropdownExpenseCategory extends Component
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
     * Create a new component instance.
     */
    public function __construct($selected = null)
    {
        $this->categories = ExpenseCategory::select('id', 'name')->get();
        $this->selected = $selected;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dropdown-expense-category');
    }
}
