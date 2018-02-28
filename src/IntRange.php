<?php
namespace mishatrinex\pgranges;


class IntRange extends Range
{
    /**
     * @param $bound
     *
     * @return int|null
     */
    public function convertBound($bound)
    {
        if ($bound === '' || $bound === null) {
            $bound = null;
        } else {
            $bound = (int) $bound;
        }

        return $bound;
    }

}
