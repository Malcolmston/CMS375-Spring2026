<?php

class Point
{
    public float $x {
        get {
            return $this->x;
        }
        set {
            $this->x = $value;
        }
    }
    public float $y {
        get {
            return $this->y;
        }
        set {
            $this->y = $value;
        }
    }

    /**
     * Calculates the Euclidean distance between two points.
     *
     * @param Point $a The starting point.
     * @param Point $b The ending point.
     * @return float The distance between point $a and point $b.
     */
    public static function distance(Point $a, Point $b): float {
        return sqrt(
            pow($b->x - $a->x, 2) +
            pow($b->y - $a->y, 2)
        );
    }
    public function __construct(float $x, float $y) {
        $this->x = $x;
        $this->y = $y;
    }

    public function getLocation(): Point {
        return $this;
    }

    /**
     * Compares the current instance with another instance of the same type for equality.
     *
     * @param self $p The instance to compare with the current instance.
     * @return bool True if the current instance is equal to the specified instance, otherwise false.
     */
    public function equals(self $p): bool {
        return $this->x === $p->x && $this->y === $p->y;
    }

    /**
     * Calculates the distance to another point.
     *
     * @param self $other The point to calculate the distance to.
     * @return float The calculated distance between the two points.
     */
    public function distanceTo(self $other): float {
        return Point::distance($this, $other);
    }

}
