<?php

namespace Wilkques\Database\Queries\Grammar;

class MySql extends Grammar
{
    /**
     * @return string
     */
    public function lockForUpdate()
    {
        return "FOR UPDATE";
    }

    /**
     * @return string
     */
    public function sharedLock()
    {
        return "LOCK IN SHARE MODE";
    }
}