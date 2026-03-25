<?php
namespace pharmaceutical;

require_once __DIR__ . '/../Connect.php';

use Connect;
use DateTime;

abstract class Pharmaceutical extends Connect
{
    protected int $id;
    protected string $manufacturer ;
    protected string $name;
    protected DateTime $createdAt;
    protected DateTime $updatedAt;
    protected DateTime $deletedAt;

    protected bool $isDeleted;

    /**
     * @throws \Exception
     */
    public function __construct(string $name, string $manufacturer)
    {
        parent::__construct();
        $this->manufacturer = $manufacturer;
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

}
