<?php

namespace App\Services;

class FormHtml
{
    public static function image($filename)
    {
        return '<img src="' . $filename . ' "/>';
    }

    public static function script($filename)
    {
        return '<script src="' . request()->root() . '/' . $filename . '"></script>';
    }

    public static function style($filename)
    {
        return '<link rel="stylesheet" href="' . $filename . '">';
    }

    public static function select()
    {
        return '';
    }

    public static function selectRange()
    {
        return '';
    }

    public static function labelWithHelp()
    {
        return '';
    }

    public static function label()
    {
        return '';
    }

    public static function text()
    {
        return '';
    }

    public static function hidden()
    {
        return '';
    }

    public static function submit()
    {
        return '';
    }

    public static function radio()
    {
        return '';
    }

    public static function checkbox()
    {
        return '';
    }

    public static function button()
    {
        return '';
    }

    public static function input()
    {
        return '';
    }

    public static function styledFile()
    {
        return '';
    }

    public static function password()
    {
        return '';
    }

    public static function rawLabel()
    {
        return '';
    }

    public static function token()
    {
        return '';
    }

    public static function textarea()
    {
        return '';
    }

    public static function model()
    {
        return '';
    }

    public static function open($params)
    {
        $method = 'POST';

        if (isset($params['method'])) {
            $method = 'GET';
        }

        if (isset($params['files'])) {
            if ($params['files'] == true) {
                $enctype = '';
            }
        }

        $form = '<form action="' . $params['url'] . '" method="' . $method . '"';

        if (isset($params['class'])) {

            $form .= ' class="' . $params['class'] ?? '' . '"';
        }

        return $form . '>';
        // return '<form action="' . $params['url'] . '" method="' . $method . '" class="' . $params['class'] ?? '' . '">';
    }

    public static function close()
    {
        return '</form>';
    }
}
