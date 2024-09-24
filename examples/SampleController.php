<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserController extends AdminController
{

    protected function grid()
    {
        return Grid::make(new User(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('mobile')->editable();
            $grid->column('email');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('name');
                $filter->between('date');
                $filter->scope('my');
                $filter->scope('latest', '最近');
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new User(['patients']), function (Show $show) {
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new User(), function (Form $form) {

            $form->submitted(function(Form $form) {
                if (!strlen((string) $form->input('password', ''))) {
                    $form->ignore('password');
                }
            });

            if ($form->isCreating()) {
                $form->text('name')->rules('unique:users');
            } else {
                $form->display('name');
            }

            $form->text('mobile');
            $form->radio('gender')->options(config('enums.gender'));
            $form->number('age');
            $form->select('type')->options([
                '1' => 'A',
                '2' => 'B',
            ]);
        });
    }
}
