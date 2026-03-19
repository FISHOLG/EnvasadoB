<?php

class encriptado    
{
    private static string $key = "SigreEsUnaFilosofiaDeVidaSigreEsUnaFilosofiaDeVida";

    public static function decrypt(string $encrypted): string
    {
        $is_key = self::$key;
        $is_encriptado = trim($encrypted);

        $li_olen = strlen($is_encriptado);
        $li_klen = strlen($is_key);

        $is_original = "";
        $li_kind = 0;

        for ($li_orig = 0; $li_orig < $li_olen; $li_orig += 3) {
            $li_ktemp = ord($is_key[$li_kind]);
            $li_work = intval(substr($is_encriptado, $li_orig, 3));
            $li_work -= $li_ktemp;

            while ($li_work < 0) {
                $li_work += 255;
            }

            $is_original .= chr($li_work);

            $li_kind++;
            if ($li_kind >= $li_klen) {
                $li_kind = 0;
            }
        }

        return $is_original;
    }
}
