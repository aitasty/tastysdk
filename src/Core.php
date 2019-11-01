<?php

namespace Aitasty;


class Core{
		public static function test(){
			$ip = get_client_ip();
			return "ait core test".$ip;
		}
}