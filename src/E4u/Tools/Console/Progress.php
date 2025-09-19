<?php
namespace E4u\Tools\Console;

class Progress
{
    static ?int $start_time;

    public static function show($done, $total, $size = 30): void
    {
        // if we go over our bound, just ignore it
        if (($done > $total) || ($total == 0)) return;

        if (empty(self::$start_time)) self::start();
        $now = time();

        $perc = (double)($done / $total);
        $bar = floor($perc * $size);

        $status_bar = "[" . str_repeat("=", $bar);
        if ($bar < $size) {
            $status_bar .= ">" . str_repeat(" ", $size - $bar);
        } else {
            $status_bar .= "=";
        }

        $disp = number_format($perc * 100, 0);
        $status_bar .= "] $disp%  $done/$total";

        $rate = $done > 0 ? ($now - self::$start_time) / $done : 0;
        $left = $total - $done;
        $eta = round($rate * $left, 2);
        $elapsed = $now - self::$start_time;

        $status_bar .= " remaining: " . self::seconds($eta)."  elapsed: " . self::seconds($elapsed);
        echo "\r$status_bar - ";
        echo sprintf('memory usage: %.2f', memory_get_usage(true) / 1048576) . " MB    ";
        flush();

        // when done, send a newline
        if ($done == $total) {
            self::$start_time = null;
            echo "\n";
        }
    }

    public static function start(): void
    {
        self::$start_time = time();
    }

    public static function seconds($x): string
    {
        if ($x < 300) {
            return $x . ' sec.';
        }

        return round($x / 60) . ' min.';
    }
}
