<?php

class TestController
{
    public function ping($request)
    {
        return [
            'ok' => true,
            'message' => 'Backend  reponde correctamente'
        ];
    }
}
