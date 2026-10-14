<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class StockGreigeDaily extends Model
{
    protected $table = 'trn_stock_greige_daily';
    protected $primaryKey = 'id';

    // Kolom created_at / updated_at menggunakan integer unix timestamp
    public $timestamps = false;

    // Asal Greige Constants
    const ASAL_GREIGE_WJL = 1;
    const ASAL_GREIGE_BELI_LOKAL = 2;
    const ASAL_GREIGE_RAPIER = 3;
    const ASAL_GREIGE_BELI_IMPORT = 4;
    const ASAL_GREIGE_LAIN_LAIN = 5;
    const ASAL_GREIGE_RETUR = 6;
    const ASAL_GREIGE_MUTASI = 7;
    const ASAL_GREIGE_PEMOTONGAN = 8;
    const ASAL_GREIGE_HASIL_MAKLOON = 9;

    // Grade constants (sesuai standar sistem Greige)
    const GRADE_A = 1;
    const GRADE_B = 2;
    const GRADE_C = 3;
    const GRADE_D = 4;
    const GRADE_E = 5;
    const GRADE_NG = 6;

    // Jenis Gudang constants
    const JG_FRESH = 1;

    // Status constants
    const STATUS_VALID = 2;

    protected $fillable = [
        'date',
        'greige_id',
        'greige_group_id',
        'asal_greige',
        'total_panjang',
        'total_roll',
        'grade_a',
        'grade_b',
        'grade_c',
        'grade_d',
        'grade_e',
        'grade_ng',
        'grade_lain',
        'note',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'greige_id' => 'integer',
        'greige_group_id' => 'integer',
        'asal_greige' => 'integer',
        'total_panjang' => 'float',
        'total_roll' => 'integer',
        'grade_a' => 'float',
        'grade_b' => 'float',
        'grade_c' => 'float',
        'grade_d' => 'float',
        'grade_e' => 'float',
        'grade_ng' => 'float',
        'grade_lain' => 'float',
        'created_at' => 'integer',
        'created_by' => 'integer',
        'updated_at' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $appends = [
        'asal_greige_name',
        'greige_nama_kain',
        'day_name',
        'formatted_date',
        'prev_total_panjang',
        'prev_total_roll',
        'prev_date',
        'diff_panjang',
        'diff_roll',
        'is_stock_masuk',
        'created_at_formatted',
    ];

    /**
     * Virtual / computed attributes
     */
    public $prev_total_panjang = 0;
    public $prev_total_roll = 0;
    public $prev_date = null;
    public $diff_panjang = 0;
    public $diff_roll = 0;

    public function getPrevTotalPanjangAttribute(): float
    {
        return (float)($this->prev_total_panjang ?? ($this->attributes['prev_total_panjang'] ?? 0));
    }

    public function getPrevTotalRollAttribute(): int
    {
        return (int)($this->prev_total_roll ?? ($this->attributes['prev_total_roll'] ?? 0));
    }

    public function getPrevDateAttribute()
    {
        return $this->prev_date ?? ($this->attributes['prev_date'] ?? null);
    }

    public function getDiffPanjangAttribute(): float
    {
        return (float)($this->diff_panjang ?? ($this->attributes['diff_panjang'] ?? 0));
    }

    public function getDiffRollAttribute(): int
    {
        return (int)($this->diff_roll ?? ($this->attributes['diff_roll'] ?? 0));
    }

    public function getIsStockMasukAttribute(): bool
    {
        return ($this->diff_panjang > 0 || $this->diff_roll > 0);
    }

    public function getCreatedAtFormattedAttribute(): string
    {
        if (empty($this->created_at)) {
            return '-';
        }
        $ts = is_numeric($this->created_at) ? (int)$this->created_at : strtotime($this->created_at);
        return $ts ? date('d-m-Y H:i:s', $ts) : '-';
    }

    /**
     * Opsi Asal Greige
     *
     * @return array
     */
    public static function asalGreigeOptions(): array
    {
        return [
            self::ASAL_GREIGE_WJL => 'Water Jet Loom',
            self::ASAL_GREIGE_BELI_LOKAL => 'Beli Lokal',
            self::ASAL_GREIGE_RAPIER => 'Rapier',
            self::ASAL_GREIGE_BELI_IMPORT => 'Beli Import',
            self::ASAL_GREIGE_LAIN_LAIN => 'Lain-lain',
            self::ASAL_GREIGE_RETUR => 'Retur',
            self::ASAL_GREIGE_MUTASI => 'Mutasi',
            self::ASAL_GREIGE_PEMOTONGAN => 'Pemotongan',
            self::ASAL_GREIGE_HASIL_MAKLOON => 'Hasil Makloon',
        ];
    }

    /**
     * Accessor untuk nama Asal Greige
     */
    public function getAsalGreigeNameAttribute(): string
    {
        return self::asalGreigeOptions()[$this->asal_greige] ?? '-';
    }

    /**
     * Relasi ke Greige / Motif
     */
    public function greige(): BelongsTo
    {
        return $this->belongsTo(Greige::class, 'greige_id');
    }

    /**
     * Relasi ke Group Greige
     */
    public function greigeGroup(): BelongsTo
    {
        return $this->belongsTo(GreigeGroup::class, 'greige_group_id');
    }

    /**
     * Relasi ke User pembuat
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke User pengupdate
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor untuk nama kain dari greige: $model->greige_nama_kain
     */
    public function getGreigeNamaKainAttribute(): string
    {
        return $this->greige ? ($this->greige->nama_kain ?? '-') : '-';
    }

    /**
     * Accessor untuk nama hari dalam bahasa Indonesia: $model->day_name
     */
    public function getDayNameAttribute(): string
    {
        return self::getIndonesianDayName($this->date);
    }

    /**
     * Accessor untuk tanggal lengkap dengan nama hari: $model->formatted_date
     */
    public function getFormattedDateAttribute(): string
    {
        return self::formatIndonesianDate($this->date, true);
    }

    /**
     * Format tanggal dengan nama hari Indonesia (Contoh: Kamis, 24 Sep 2026)
     *
     * @param string|\DateTimeInterface|null $dateStr
     * @param bool $withDay
     * @return string
     */
    public static function formatIndonesianDate($dateStr, bool $withDay = true): string
    {
        if (empty($dateStr)) {
            return '-';
        }

        $timestamp = is_numeric($dateStr) ? (int)$dateStr : strtotime($dateStr);
        if (!$timestamp) {
            return '-';
        }

        $days = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $dayName = $days[date('l', $timestamp)] ?? '';
        $d = date('d', $timestamp);
        $m = $months[(int)date('n', $timestamp)] ?? '';
        $y = date('Y', $timestamp);

        if ($withDay) {
            return "{$dayName}, {$d} {$m} {$y}";
        }

        return "{$d} {$m} {$y}";
    }

    /**
     * Mengambil nama hari bahasa Indonesia
     *
     * @param string|\DateTimeInterface|null $dateStr
     * @return string
     */
    public static function getIndonesianDayName($dateStr): string
    {
        if (empty($dateStr)) {
            return '';
        }

        $timestamp = is_numeric($dateStr) ? (int)$dateStr : strtotime($dateStr);
        if (!$timestamp) {
            return '';
        }

        $days = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        return $days[date('l', $timestamp)] ?? '';
    }

    /**
     * Snapshot stock greige dari gudang fresh (status valid) untuk tanggal tertentu
     * Agregasi dilakukan per motif (greige_id), group (greige_group_id), dan asal_greige.
     *
     * @param string|null $date Format: Y-m-d (default: hari ini)
     * @param int|null $userId ID user yang melakukan snapshot
     * @return array
     * @throws \Throwable
     */
    public static function snapshotStock(?string $date = null, ?int $userId = null): array
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        // Ambil data agregat stock fresh valid dari trn_stock_greige per motif dan asal_greige
        $rows = DB::table('trn_stock_greige as tsg')
            ->select([
                'tsg.greige_id',
                'tsg.greige_group_id',
                'tsg.asal_greige',
                DB::raw('COALESCE(SUM(tsg.panjang_m), 0) as total_panjang'),
                DB::raw('COUNT(tsg.id) as total_roll'),
                DB::raw('COALESCE(SUM(CASE WHEN tsg.grade = ' . self::GRADE_A . ' THEN tsg.panjang_m ELSE 0 END), 0) as grade_a'),
                DB::raw('COALESCE(SUM(CASE WHEN tsg.grade = ' . self::GRADE_B . ' THEN tsg.panjang_m ELSE 0 END), 0) as grade_b'),
                DB::raw('COALESCE(SUM(CASE WHEN tsg.grade = ' . self::GRADE_C . ' THEN tsg.panjang_m ELSE 0 END), 0) as grade_c'),
                DB::raw('COALESCE(SUM(CASE WHEN tsg.grade = ' . self::GRADE_D . ' THEN tsg.panjang_m ELSE 0 END), 0) as grade_d'),
                DB::raw('COALESCE(SUM(CASE WHEN tsg.grade = ' . self::GRADE_E . ' THEN tsg.panjang_m ELSE 0 END), 0) as grade_e'),
                DB::raw('COALESCE(SUM(CASE WHEN tsg.grade = ' . self::GRADE_NG . ' THEN tsg.panjang_m ELSE 0 END), 0) as grade_ng'),
                DB::raw('COALESCE(SUM(CASE WHEN tsg.grade NOT IN (' . implode(',', [
                    self::GRADE_A,
                    self::GRADE_B,
                    self::GRADE_C,
                    self::GRADE_D,
                    self::GRADE_E,
                    self::GRADE_NG,
                ]) . ') THEN tsg.panjang_m ELSE 0 END), 0) as grade_lain'),
            ])
            ->where('tsg.jenis_gudang', self::JG_FRESH)
            ->where('tsg.status', self::STATUS_VALID)
            ->where(function ($q) use ($date) {
                $q->whereNull('tsg.date')->orWhere('tsg.date', '<=', $date);
            })
            ->groupBy('tsg.greige_id', 'tsg.greige_group_id', 'tsg.asal_greige')
            ->get();

        return DB::transaction(function () use ($rows, $date, $userId) {
            $savedCount = 0;
            $updatedCount = 0;
            $grandTotalM = 0;
            $grandTotalRoll = 0;
            $now = time();

            foreach ($rows as $row) {
                $greigeId = (int)$row->greige_id;
                $greigeGroupId = (int)$row->greige_group_id;
                $asalGreige = (int)$row->asal_greige;

                $model = self::where('date', $date)
                    ->where('greige_id', $greigeId)
                    ->where('asal_greige', $asalGreige)
                    ->first();

                $isNew = false;
                if (!$model) {
                    $model = new self();
                    $model->date = $date;
                    $model->greige_id = $greigeId;
                    $model->greige_group_id = $greigeGroupId;
                    $model->asal_greige = $asalGreige;
                    $model->created_at = $now;
                    $model->created_by = $userId;
                    $isNew = true;
                } else {
                    $model->greige_group_id = $greigeGroupId;
                }

                $model->total_panjang = (float)$row->total_panjang;
                $model->total_roll = (int)$row->total_roll;
                $model->grade_a = (float)$row->grade_a;
                $model->grade_b = (float)$row->grade_b;
                $model->grade_c = (float)$row->grade_c;
                $model->grade_d = (float)$row->grade_d;
                $model->grade_e = (float)$row->grade_e;
                $model->grade_ng = (float)$row->grade_ng;
                $model->grade_lain = (float)$row->grade_lain;
                $model->updated_at = $now;
                $model->updated_by = $userId;

                $model->save();

                if ($isNew) {
                    $savedCount++;
                } else {
                    $updatedCount++;
                }

                $grandTotalM += (float)$row->total_panjang;
                $grandTotalRoll += (int)$row->total_roll;
            }

            return [
                'success' => true,
                'date' => $date,
                'total_motifs' => count($rows),
                'new_saved' => $savedCount,
                'updated' => $updatedCount,
                'grand_total_m' => $grandTotalM,
                'grand_total_roll' => $grandTotalRoll,
            ];
        });
    }
}
