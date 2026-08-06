<?php

namespace App\Enums;

enum ParentescoFamiliar: string
{
    case Madre = '1.MADRE';
    case Padre = '2.PADRE';
    case Hermano = '3.HERMANO';
    case AbueloMaterna = '4.ABUELO(A) MATERNA';
    case AbueloPaterna = '5.ABUELO(A) PATERNA';
    case TioMaterna = '6.TIO(A) MATERNA';
    case TioPaterna = '7.TIO(A) PATERNA';
}
