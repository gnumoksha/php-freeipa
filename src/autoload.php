<?php
// FreeIPA library for PHP
// Copyright (C) 2015 Tobias Sette <contato@tobias.ws>
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program.If not, see <http://www.gnu.org/licenses/>.


// https://github.com/sebastianbergmann/money/blob/master/src/autoload.php

spl_autoload_register(
    
  function( $class ) {
    static $classes = null;
    
    if ( $classes === null ) {
      $classes = array(
        'freeipa\\apiaccess' => '/APIAccess.php',
      );
    }
    
    $cn = strtolower($class);
    if ( isset( $classes[$cn] ) ) {
      $n = __DIR__ . $classes[$cn];
      require __DIR__ . $classes[$cn];
    }
  }
  
);
