<?php


if (!function_exists('searchThroughArray')) {
    function searchThroughArray($search, array $lists)
    {
        try {
            foreach ($lists as $key => $value) {
                if (is_array($value)) {
                    array_walk_recursive($value, function ($v, $k) use ($search, $key, $value, &$val) {
                        if (strpos($v, $search) !== false)  $val[$key] = $value;
                    });
                } else {
                    if (strpos($value, $search) !== false)  $val[$key] = $value;
                }
            }
            return $val;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('endKey')) {
    //Funcion para obtener el ultimo indice de un array
    function endKey($array)
    {

        //Aquí utilizamos end() para poner el puntero
        //en el último elemento, no para devolver su valor
        end($array);

        return key($array);
    }
}
