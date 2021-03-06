<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'model/cuenta.php';
require_once 'model/ejercicio.php';
require_once 'model/epigrafe.php';

class contabilidad_cuentas extends fs_controller
{
   public $cuenta;
   public $cuentas_especiales;
   public $ejercicio;
   public $epigrafes;
   public $resultados;
   public $resultados2;
   public $offset;

   public function __construct()
   {
      parent::__construct('contabilidad_cuentas', 'Cuentas', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->cuenta = new cuenta();
      $this->ejercicio = new ejercicio();
      $this->epigrafes = array();
      $this->custom_search = TRUE;
      
      $ce = new cuenta_especial();
      $this->cuentas_especiales = $ce->all();
      
      $this->buttons[] = new fs_button_img('b_nueva_cuenta', 'nueva');
      $this->buttons[] = new fs_button('b_cuentas_especiales', 'Cuentas especiales');
      $this->buttons[] = new fs_button('b_balances', 'Balances', 'index.php?page=contabilidad_balances');
      
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;
      
      if( isset($_GET['delete']) )
      {
         $cuenta2 = $this->cuenta->get($_GET['delete']);
         if($cuenta2)
         {
            if( $cuenta2->delete() )
               $this->new_message('Cuenta eliminada correctamente.');
            else
               $this->new_error_msg('Error al eliminar la cuenta.');
         }
         else
            $this->new_error_msg('Cuenta no encontrada.');
         
         $this->resultados = $this->cuenta->all_from_ejercicio($this->default_items->codejercicio(), $this->offset);
         $this->resultados2 = array();
      }
      else if( isset($_POST['codejercicio']) )
      {
         $this->nueva_cuenta();
      }
      else if($this->query != '')
      {
         $this->resultados = $this->cuenta->search($this->query);
         $subc = new subcuenta();
         $this->resultados2 = $subc->search($this->query);
      }
      else if( isset($_POST['ejercicio']) )
      {
         $this->save_codejercicio( $_POST['ejercicio'] );
         $this->resultados = $this->cuenta->all_from_ejercicio($_POST['ejercicio'], $this->offset);
         $this->resultados2 = array();
      }
      else
      {
         $this->resultados = $this->cuenta->all_from_ejercicio($this->default_items->codejercicio(), $this->offset);
         $this->resultados2 = array();
      }
   }
   
   public function version()
   {
      return parent::version().'-6';
   }
   
   public function anterior_url()
   {
      $url = '';
      if($this->query!='' AND $this->offset>0)
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT);
      else if($this->query=='' AND $this->offset>0)
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT);
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      return $url;
   }
   
   private function nueva_cuenta()
   {
      if( isset($_POST['idepigrafe']) )
      {
         $epi = new epigrafe();
         $epi0 = $epi->get($_POST['idepigrafe']);
         if($epi0)
         {
            $cuenta = new cuenta();
            $cuenta2 = $cuenta->get_by_codigo($_POST['codcuenta'], $_POST['codejercicio']);
            if($cuenta2)
               $this->new_error_msg('Ya existe la cuenta '.$cuenta2->codcuenta);
            else
            {
               $cuenta->codcuenta = $_POST['codcuenta'];
               $cuenta->codejercicio = $_POST['codejercicio'];
               $cuenta->codepigrafe = $epi0->codepigrafe;
               $cuenta->descripcion = $_POST['descripcion'];
               $cuenta->idepigrafe = $epi0->idepigrafe;
               if( $cuenta->save() )
                  header('Location: '.$cuenta->url());
               else
                  $this->new_error_msg('Se ha producido un error al crear la cuenta.');
            }
         }
         else
            $this->new_error_msg('Epígrafe no encontrado.');
         
         $this->resultados = $this->cuenta->all($this->offset);
         $this->resultados2 = array();
      }
      else
      {
         /// cambiamos la plantilla HTML
         $this->template = 'ajax/contabilidad_nueva_cuenta';
         
         $epi = new epigrafe();
         $this->epigrafes = $epi->all_from_ejercicio($_POST['codejercicio']);
      }
   }
}

?>