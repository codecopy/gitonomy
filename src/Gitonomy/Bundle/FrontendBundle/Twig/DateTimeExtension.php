<?php

namespace Gitonomy\Bundle\FrontendBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session;

use Gitonomy\Bundle\CoreBundle\Entity\User;

/**
 * Twig extension for date/time formatting.
 */
class DateTimeExtension extends \Twig_Extension
{
    /**
     * Associative array of formatters
     *
     * @var array An array mapping type to formatter
     */
    protected $formatters = array();

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inherited}
     */
    public function getFilters()
    {
        return array(
            'date_long'      => new \Twig_Filter_Method($this, 'getDateLong'),
            'date_short'     => new \Twig_Filter_Method($this, 'getDateShort'),
            'datetime_long'  => new \Twig_Filter_Method($this, 'getDateTimeLong'),
            'datetime_short' => new \Twig_Filter_Method($this, 'getDateTimeShort'),
            'time_short'     => new \Twig_Filter_Method($this, 'getTimeShort'),
            'dayname_short'  => new \Twig_Filter_Method($this, 'getDaynameShort'),
            'dayname_long'   => new \Twig_Filter_Method($this, 'getDaynameLong'),
        );
    }

    /**
     * Formats date in long.
     *
     * @param \DateTime $dateTime
     *
     * @return string
     */
    public function getDateLong(\DateTime $dateTime)
    {
        return $this
            ->getFormatter(\IntlDateFormatter::LONG)
            ->format($dateTime->getTimestamp())
        ;
    }

    /**
     * Formats date in short.
     *
     * @param \DateTime $dateTime
     *
     * @return string
     */
    public function getDateShort(\DateTime $dateTime)
    {
        return $this
            ->getFormatter(\IntlDateFormatter::SHORT)
            ->format($dateTime->getTimestamp())
        ;
    }

    /**
     * Formats date and time in long.
     *
     * @param \DateTime $dateTime
     *
     * @return string
     */
    public function getDateTimeLong(\DateTime $dateTime)
    {
        return $this
            ->getFormatter(\IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM)
            ->format($dateTime->getTimestamp())
        ;
    }

    /**
     * Formats date and time in short.
     *
     * @param \DateTime $dateTime
     *
     * @return string
     */
    public function getDateTimeShort(\DateTime $dateTime)
    {
        return $this
            ->getFormatter(\IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT)
            ->format($dateTime->getTimestamp())
        ;
    }

    /**
     * Formats time in short.
     *
     * @param \DateTime $dateTime
     *
     * @return string
     */
    public function getTimeShort(\DateTime $dateTime)
    {
        return $this
            ->getFormatter(\IntlDateFormatter::NONE, \IntlDateFormatter::SHORT)
            ->format($dateTime->getTimestamp())
        ;
    }

    /**
     * Returns the day name in short.
     *
     * @param \DateTime $dateTime
     *
     * @return string
     */
    public function getDayNameShort(\DateTime $dateTime)
    {
        $formatter = $this->getFormatter(\IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
        $formatter->setPattern('EE');

        return $formatter->format($dateTime->getTimestamp());
    }

    /**
     * Returns the full day name.
     *
     * @param \DateTime $dateTime
     *
     * @return string
     */
    public function getDayNameLong(\DateTime $dateTime)
    {
        $formatter = $this->getFormatter(\IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
        $formatter->setPattern('EEEE');
        return $formatter->format($dateTime->getTimestamp());
    }

    /**
     * {@inherited}
     */
    public function getName()
    {
        return 'localized_date';
    }

    /**
     * Get the formatter for a given format
     *
     * @param int $dateFormat The date format (IntlDateFormatter::* constants)
     * @param int $timeFormat The time format (IntlDateFormatter::* constants)
     *
     * @return IntlDateFormatter
     */
    protected function getFormatter($dateFormat, $timeFormat = \IntlDateFormatter::NONE)
    {
        $locale = $this->container->get('request')->getLocale();

        $user = $timezone = $this->container->get('security.context')->getToken()->getUser();
        if ($user instanceof User) {
            $timezone = $user->getTimezone();
        } else {
            $timezone = date_default_timezone_get();
        }

        $key = $dateFormat . '_' . $timeFormat.'_'.$locale.'_'.$timezone;
        if (false === isset($this->formatters[$key])) {
            $this->formatter[$key] = new \IntlDateFormatter($locale, $dateFormat, $timeFormat, $timezone);
        }
        return $this->formatter[$key];
    }
}