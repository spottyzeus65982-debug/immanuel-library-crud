<?php

// Input teks yang sudah dibersihkan. Selain string (misal ?name[]=x) dianggap kosong.
function cleanInput($value)
{
    return is_string($value) ? trim(htmlspecialchars($value)) : '';
}

// Input apa adanya (untuk kata sandi). Selain string dianggap kosong.
function rawInput($value)
{
    return is_string($value) ? $value : '';
}
