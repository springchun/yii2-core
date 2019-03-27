<?php
namespace springchun\yii2\core\snowflake;
use yii\base\Component;

/**
 * Class SnowFlake
 * @package exts\snowflake
 */
class SnowFlake extends Component
{
    /**
     * Offset from Unix Epoch
     * Unix Epoch : January 1 1970 00:00:00 GMT
     * Epoch Offset : January 1 2000 00:00:00 GMT
     */
    const EPOCH_OFFSET = 1483200000000;
    const SIGN_BITS = 1;
    const TIMESTAMP_BITS = 41;
    const DATACENTER_BITS = 5;
    const MACHINE_ID_BITS = 5;
    const SEQUENCE_BITS = 12;

    /**
     * @var mixed
     */
    protected $datacenter_id = 1024;

    /**
     * @var mixed
     */
    protected $machine_id = 1;

    /**
     * @var null|int
     */
    protected $lastTimestamp = null;

    /**
     * @var int
     */
    protected $sequence = 1;
    protected $signLeftShift;
    protected $timestampLeftShift;
    protected $dataCenterLeftShift;
    protected $machineLeftShift;
    protected $maxSequenceId;
    protected $maxMachineId;
    protected $maxDataCenterId;

    /**
     * @param bool $hex
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function getId($hex = false)
    {
        static $snowflake;
        if (!$snowflake) {
            if (!$snowflake = \Yii::$app->get('snowflake', false)) {
                $snowflake = new static();
            }
        }
        /**
         * 返回16进制数据
         */
        if ($hex) {
            return static::dec2hex($snowflake->next());
        }
        return $snowflake->next();
    }


    /**
     * @param $id
     * @return string
     */
    public static function dec2hex($id)
    {
        $hex = dechex($id);
        $hex = str_pad($hex, 15, '0', STR_PAD_LEFT);
        $hex = str_pad($hex, 16, '5', STR_PAD_LEFT);
        $rand = md5(microtime(1));
        return implode('-', [
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($rand, 0, 4),
            substr($rand, 4, 8)
        ]);
    }

    /**
     *
     */
    public function init()
    {
        parent::init();
        $this->signLeftShift = self::TIMESTAMP_BITS + self::DATACENTER_BITS + self::MACHINE_ID_BITS + self::SEQUENCE_BITS;
        $this->timestampLeftShift = self::DATACENTER_BITS + self::MACHINE_ID_BITS + self::SEQUENCE_BITS;
        $this->dataCenterLeftShift = self::MACHINE_ID_BITS + self::SEQUENCE_BITS;
        $this->machineLeftShift = self::SEQUENCE_BITS;
        $this->maxSequenceId = -1 ^ (-1 << self::SEQUENCE_BITS);
        $this->maxMachineId = -1 ^ (-1 << self::MACHINE_ID_BITS);
        $this->maxDataCenterId = -1 ^ (-1 << self::DATACENTER_BITS);
    }


    /**
     * Generate an unique ID based on SnowFlake
     * @return string
     * @throws \Exception
     */
    public function next()
    {

        $sign = 0; // default 0
        $timestamp = $this->getUnixTimestamp();
        if ($timestamp < $this->lastTimestamp) {
            throw new \Exception('"Clock moved backwards!');
        }
        if ($timestamp == $this->lastTimestamp) { //与上次时间戳相等，需要生成序列号
            $sequence = ++$this->sequence;
            if ($sequence == $this->maxSequenceId) { //如果序列号超限，则需要重新获取时间
                $timestamp = $this->getUnixTimestamp();
                while ($timestamp <= $this->lastTimestamp) {
                    $timestamp = $this->getUnixTimestamp();
                }
                $this->sequence = 0;
                $sequence = ++$this->sequence;
            }
        } else {
            $this->sequence = 0;
            $sequence = ++$this->sequence;
        }
        $this->lastTimestamp = $timestamp;
        $time = (int)($timestamp - self::EPOCH_OFFSET);
        $id = ($sign << $this->signLeftShift) | ($time << $this->timestampLeftShift) | ($this->datacenter_id << $this->dataCenterLeftShift) | ($this->machine_id << $this->machineLeftShift) | $sequence;
        return (string)$id;
    }

    /**
     * Get UNIX timestamp in microseconds
     *
     * @return int  Timestamp in microseconds
     */
    private function getUnixTimestamp()
    {
        return floor(microtime(true) * 1000);
    }
}
