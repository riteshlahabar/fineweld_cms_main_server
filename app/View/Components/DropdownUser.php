<?php

namespace App\View\Components;

use App\Models\User;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DropdownUser extends Component
{
    /**
     * Roles array
     *
     * @var array
     */
    public $users;

    /**
     * Selected option
     *
     * @var string
     */
    public $selected;

    /**
     * Selected option
     *
     * @var bool
     */
    public $disabled;

    /**
     * Show Only Username
     *
     * @return bool
     * */
    public $showOnlyUsername;

    /**
     * Permission to View All Users
     */
    public $canViewAllUsers;

    /**
     * Create a new component instance.
     */
    public function __construct($selected = null, $disabled = false, $showOnlyUsername = false, $canViewAllUsers = true)
    {
        $this->users = User::select('id', 'first_name', 'last_name', 'username')
            ->when(! $canViewAllUsers, fn ($query) => $query->where('id', auth()->id()))->get();
        $this->selected = $selected;
        $this->disabled = $disabled;
        $this->showOnlyUsername = $showOnlyUsername;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dropdown-user');
    }
}
