<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DropdownStatus extends Component
{
    /**
     * Selected option
     *
     * @var string
     */
    public $selected;

    /**
     * Options list
     *
     * @var array
     */
    public $dropdownData;

    /**
     * Dropdown name or id attribute
     *
     * @var string
     */
    public $dropdownName;

    /**
     * Dropdown option naming - Active/Inactive or Enable/Disable
     *
     * @var string
     */
    public $optionNaming;

    /**
     * Create a new component instance.
     */
    public function __construct($dropdownName, $selected = null, $optionNaming = null)
    {
        // Declare var optionNaming before this dropdownData() mthod
        $this->optionNaming = $optionNaming;
        $this->dropdownData = $this->dropdownData();
        $this->selected = $selected;
        $this->dropdownName = $dropdownName;

    }

    public function dropdownData()
    {
        if ($this->optionNaming == 'EnableDisable') {
            return [
                [
                    'status' => 1,
                    'name' => 'Enable',
                ],
                [
                    'status' => 0,
                    'name' => 'Disable',
                ],
            ];
        } elseif ($this->optionNaming == 'ActiveInactive') {
            return [
                [
                    'status' => 'active',
                    'name' => 'Active',
                ],
                [
                    'status' => 'inactive',
                    'name' => 'Inactive',
                ],
            ];
        } elseif ($this->optionNaming == 'InclusiveExclusive') {
            return [
                [
                    'status' => 'inclusive',
                    'name' => 'Inclusive',
                ],
                [
                    'status' => 'exclusive',
                    'name' => 'Exclusive',
                ],
            ];
        } else {
            return [
                [
                    'status' => 1,
                    'name' => 'Active',
                ],
                [
                    'status' => 0,
                    'name' => 'Inactive',
                ],
            ];
        }

    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dropdown-status');
    }
}
