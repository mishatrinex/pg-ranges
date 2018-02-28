<?php
namespace mishatrinex\pgranges;


class NumRange extends Range
{
    /**
     * @param $bound
     *
     * @return float|null
     */
    public function convertBound($bound)
    {
        if ($bound === '' || $bound === null) {
            $bound = null;
        } else {
            $bound = (float) $bound;
        }

        return $bound;
    }

}
