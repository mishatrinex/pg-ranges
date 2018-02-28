<?php

namespace mishatrinex\pgranges;

/**
 * Class Range
 *
 * @package mishatrinex\pgranges
 */
abstract class Range
{
    /** @var int|float|mixed */
    private $lowerBound;

    /** @var int|float|mixed */
    private $upperBound;

    /** @var bool  */
    private $isLowerBoundInclusive;

    /** @var bool  */
    private $isUpperBoundInclusive;


    /**
     * This method uses for typing conversion when bound is setting
     * @param $bound
     * @return mixed
     */
    abstract public function convertBound($bound);

    /**
     * Range constructor.
     *
     * @param      $lowerBound
     * @param      $upperBound
     * @param bool $isLowerBoundInclusive
     * @param bool $isUpperBoundInclusive
     *
     * @throws     \OverflowException
     */
    public function __construct(
        $lowerBound,
        $upperBound,
        bool $isLowerBoundInclusive = true,
        bool $isUpperBoundInclusive = true
    ) {

        $this->setLowerBound($lowerBound);
        $this->setUpperBound($upperBound);

        $this->checkBounds();
        $this->isLowerBoundInclusive = $isLowerBoundInclusive;
        $this->isUpperBoundInclusive = $isUpperBoundInclusive;
    }

    /**
     * @return int|null
     */
    public function getLowerBound()
    {
        return $this->lowerBound;
    }

    /**
     * @return int|null
     */
    public function getUpperBound()
    {
        return $this->upperBound;
    }

    /**
     * Paring Postgres range format: ``[0.5,1.0)``, where "[" is inclusive bound and ")" is exclusive bound
     * No bound marks as empty string and always has exclusive bound: ``(,1.0]``
     * @see https://www.postgresql.org/docs/current/static/rangetypes.html
     * @param string $value
     *
     * @return static
     * @throws \UnexpectedValueException
     * @throws \OverflowException
     */
    public static function fromString(string $value)
    {
        $arrayed = str_split($value);

        $lowerIncExl = null;
        $upperIncExl = null;
        $lowerBound  = null;
        $upperBound  = null;

        $isBottom = false;
        $isUpper  = false;

        foreach ($arrayed as $pos => $char) {
            if ($char === '(' || $char === '[') {
                $lowerIncExl = $char;
                $isBottom    = true;
                continue;
            }

            if ($isBottom === true) {
                if ($char === ',') {
                    $isUpper  = true;
                    $isBottom = false;
                    continue;
                }

                $lowerBound .= $char;
                continue;

            }

            if ($isUpper === true) {
                if ($char === ')' || $char === ']') {
                    $upperIncExl = $char;
                    $isUpper     = false;
                    continue;
                }

                $upperBound .= $char;
            }
        }

        if (!$lowerIncExl || !$upperIncExl) {
            throw new \UnexpectedValueException(sprintf('Expected range string format, got: %s', $value));
        }

        $isBottomInclusive = $lowerIncExl === '[';
        $isUpperInclusive  = $upperIncExl === ']';

        return new static($lowerBound, $upperBound, $isBottomInclusive, $isUpperInclusive);
    }

    /**
     * @return bool
     */
    public function isLowerBoundInclusive(): bool
    {
        return $this->isLowerBoundInclusive;
    }

    /**
     * @return bool
     */
    public function isUpperBoundInclusive(): bool
    {
        return $this->isUpperBoundInclusive;
    }

    /**
     * Convert object to string representation
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('%s%s,%s%s',
            $this->isLowerBoundInclusive() ? '[' : '(',
            $this->getLowerBound(),
            $this->getUpperBound(),
            $this->isUpperBoundInclusive() ? ']' : ')'
        );
    }

    /**
     * @param           $upperBound
     * @param bool|null $inclusive
     */
    public function setUpperBound($upperBound, bool $inclusive = null)
    {
        $this->upperBound = $this->convertBound($upperBound);
        if ($inclusive !== null) {
            $this->isUpperBoundInclusive = $inclusive;
        }
    }

    /**
     * @param           $bottomBound
     * @param bool|null $inclusive
     */
    public function setLowerBound($bottomBound, bool $inclusive = null)
    {
        $this->lowerBound = $this->convertBound($bottomBound);
        if ($inclusive !== null) {
            $this->isLowerBoundInclusive = $inclusive;
        }
    }

    /**
     * @throws \OverflowException
     */
    public function checkBounds()
    {
        $upper  = $this->getUpperBound();
        $bottom = $this->getLowerBound();

        if ($upper !== null
            && $bottom !== null
            && $bottom > $upper) {
            throw new \OverflowException(
                sprintf('Upper bound (%s) less then bottom bound (%s)',
                    $this->getUpperBound(),
                    $this->getLowerBound())
            );
        }
    }
}
