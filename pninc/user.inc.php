<?PHP
/************************************************************************/
/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2023 PowerScripts                                 */
/*                                                                      */
/* This program is free software; you can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License, or    */
/* (at your option) any later version.                                  */
/*                                                                      */
/* This program is distributed in the hope that it will be useful,      */
/* but WITHOUT ANY WARRANTY; without even the implied warranty of       */
/* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        */
/* GNU General Public License for more details.                         */
/*                                                                      */
/* You should have received a copy of the GNU General Public License    */
/* along with this program; if not, write to the Free Software          */
/* Foundation, Inc., 59 Temple Place, Suite 330, Boston,                */
/* MA  02111-1307  USA                                                  */
/************************************************************************/

  $pn_user = new pn_user;
  $page = $_GET['page'] ?? '';
  switch ($page) {
    case "logout":
      $pn_user->logout();
    break;
    case "profile":
      $pn_user->profile();
    break;
    case "senddata":
      $pn_user->senddata();
    break;
    case "login":
      $pn_user->login();
    break;
    default:
      $pn_user->register();
    break;
  }

  pn_cpi();

?>
