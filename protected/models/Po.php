<?php

/**
 * This is the model class for table "po".
 *
 * The followings are the available columns in table 'po':
 * @property string $id
 * @property string $nomor
 * @property string $tanggal
 * @property string $profil_id
 * @property string $referensi
 * @property string $tanggal_referensi
 * @property integer $status
 * @property string $pembelian_id
 * @property string $updated_at
 * @property string $updated_by
 * @property string $created_at
 *
 * The followings are the available model relations:
 * @property Pembelian $pembelian
 * @property Profil $profil
 * @property User $updatedBy
 * @property PoDetail[] $poDetails
 */
class Po extends CActiveRecord
{
    const STATUS_DRAFT = 0;
    const STATUS_PO    = 10;
    /* ===================== */
    const KERTAS_LETTER = 10;
    const KERTAS_A4     = 20;
    const KERTAS_FOLIO  = 30;
    /* ===================== */
    const KERTAS_LETTER_NAMA = 'Letter';
    const KERTAS_A4_NAMA     = 'A4';
    const KERTAS_FOLIO_NAMA  = 'Folio';

    public $max; // Untuk mencari untuk nomor surat;
    public $namaSupplier;
    public $namaUpdatedBy;
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'po';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['profil_id', 'required', 'message' => '{attribute} tidak boleh kosong'],
            ['status', 'numerical', 'integerOnly' => true],
            ['nomor, referensi', 'length', 'max' => 45],
            ['profil_id, pembelian_id, updated_by', 'length', 'max' => 10],
            ['tanggal_referensi, created_at, tanggal, updated_at, updated_by', 'safe'],
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            ['id, nomor, tanggal, profil_id, referensi, tanggal_referensi, status, pembelian_id, updated_at, updated_by, created_at', 'safe', 'on' => 'search'],
        ];
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return [
            'pembelian' => [self::BELONGS_TO, 'Pembelian', 'pembelian_id'],
            'profil'    => [self::BELONGS_TO, 'Profil', 'profil_id'],
            'updatedBy' => [self::BELONGS_TO, 'User', 'updated_by'],
            'poDetails' => [self::HAS_MANY, 'PoDetail', 'po_id'],
        ];
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'id'                => 'ID',
            'nomor'             => 'Nomor',
            'tanggal'           => 'Tanggal',
            'profil_id'         => 'Profil',
            'referensi'         => 'Referensi',
            'tanggal_referensi' => 'Tanggal Referensi',
            'status'            => 'Status',
            'pembelian_id'      => 'Pembelian',
            'updated_at'        => 'Updated At',
            'updated_by'        => 'Updated By',
            'created_at'        => 'Created At',
        ];
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id, true);
        $criteria->compare('nomor', $this->nomor, true);
        $criteria->compare('tanggal', $this->tanggal, true);
        $criteria->compare('profil_id', $this->profil_id, true);
        $criteria->compare('referensi', $this->referensi, true);
        $criteria->compare('tanggal_referensi', $this->tanggal_referensi, true);
        $criteria->compare('status', $this->status);
        $criteria->compare('pembelian_id', $this->pembelian_id, true);
        $criteria->compare('updated_at', $this->updated_at, true);
        $criteria->compare('updated_by', $this->updated_by, true);
        $criteria->compare('created_at', $this->created_at, true);

        $criteria->with = ['profil', 'updatedBy'];
        $criteria->compare('profil.nama', $this->namaSupplier, true);
        $criteria->compare('updatedBy.nama_lengkap', $this->namaUpdatedBy, true);

        $sort = [
            'defaultOrder' => 't.status, t.tanggal desc',
            'attributes' => [
                'namaSupplier' => [
                    'asc' => 'profil.nama',
                    'desc' => 'profil.nama desc'
                ],
                'namaUpdatedBy' => [
                    'asc' => 'updatedBy.nama_lengkap',
                    'desc' => 'updatedBy.nama_lengkap desc'
                ],
                '*'
            ]
        ];

        return new CActiveDataProvider($this, [
            'criteria' => $criteria,
            'sort' => $sort
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Po the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function beforeSave()
    {
        if ($this->isNewRecord) {
            $this->created_at = date('Y-m-d H:i:s');
            /*
             * Tanggal akan diupdate jika melalui proses simpan
             * bersamaan dengan dapat nomor
             */
            $this->tanggal = date('Y-m-d H:i:s');
        }
        $this->updated_at = date('Y-m-d H:i:s');
        $this->updated_by = Yii::app()->user->id;

        // Jika disimpan melalui proses simpan
        if ($this->scenario === 'simpan') {
            // Status diubah jadi po
            $this->status = self::STATUS_PO;
            // Dapat nomor dan tanggal
            $this->tanggal = date('Y-m-d H:i:s');
            $this->nomor   = $this->generateNomor6Seq();
        }

        return parent::beforeSave();
    }

    public function beforeValidate()
    {
        $this->tanggal_referensi = !empty($this->tanggal_referensi) ? date_format(date_create_from_format('d-m-Y', $this->tanggal_referensi), 'Y-m-d') : null;
        return parent::beforeValidate();
    }

    public function afterFind()
    {
        $this->tanggal           = !is_null($this->tanggal) ? date_format(date_create_from_format('Y-m-d H:i:s', $this->tanggal), 'd-m-Y H:i:s') : '0';
        $this->tanggal_referensi = !is_null($this->tanggal_referensi) ? date_format(date_create_from_format('Y-m-d', $this->tanggal_referensi), 'd-m-Y') : '';
        return parent::afterFind();
    }

    /**
     * Mencari nomor untuk penomoran surat
     * @return int maksimum+1 atau 1 jika belum ada nomor untuk tahun ini
     */
    public function cariNomorTahunan()
    {
        $tahun = date('y');
        $data  = $this->find(
            [
            'select'    => 'max(substring(nomor,9)*1) as max',
            'condition' => "substring(nomor,5,2)='{$tahun}'"]
        );

        $value = is_null($data) ? 0 : $data->max;
        return $value + 1;
    }

    /**
     * Membuat nomor surat, 6 digit sequence number
     * @return string Nomor sesuai format "[KodeCabang][kodeDokumen][Tahun][Bulan][SequenceNumber]"
     */
    public function generateNomor6Seq()
    {
        $config         = Config::model()->find("nama='toko.kode'");
        $kodeCabang     = $config->nilai;
        $kodeDokumen    = KodeDokumen::PO;
        $kodeTahunBulan = date('ym');
        $sequence       = substr('00000' . $this->cariNomorTahunan(), -6);
        return "{$kodeCabang}{$kodeDokumen}{$kodeTahunBulan}{$sequence}";
    }

    /**
     * Total PO
     * @return int Nilai Total
     */
    public function getTotalRaw()
    {
        $po = Yii::app()->db->createCommand()
            ->select('sum(harga_beli_terakhir * qty_order) total')
            ->from(PoDetail::model()->tableName())
            ->where('po_id=:poId', [':poId' => $this->id])
            ->queryRow();
        return $po['total'];
    }

    /**
     * Nilai total PO
     * @return text Total PO dalam format ribuan
     */
    public function getTotal()
    {
        return number_format($this->totalRaw, 0, ',', '.');
    }

    public function simpan()
    {
        $this->scenario = 'simpan';
        $transaction    = $this->dbConnection->beginTransaction();

        try {
            if ($this->save()) {
                $transaction->commit();
                return ['sukses' => true];
            } else {
                throw new Exception('Gagal Simpan PO');
            }
        } catch (Exception $ex) {
            $transaction->rollback();
            return [
                'sukses' => false,
                'error'  => [
                    'msg'  => $ex->getMessage(),
                    'code' => $ex->getCode(),
            ]];
        }
    }

    public function statusList()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PO    => 'PO',
        ];
    }

    public function getNamaStatus()
    {
        return $this->statusList()[$this->status];
    }

    public static function listNamaKertas()
    {
        return [
            self::KERTAS_A4     => self::KERTAS_A4_NAMA,
            self::KERTAS_LETTER => self::KERTAS_LETTER_NAMA,
            self::KERTAS_FOLIO  => self::KERTAS_FOLIO_NAMA,
        ];
    }

    /**
     * Buat Pembelian dari PO ini
     */
    public function beli()
    {
        $transaction = $this->dbConnection->beginTransaction();
        try {
            $pembelian                    = new Pembelian;
            $pembelian->profil_id         = $this->profil_id;
            $pembelian->referensi         = $this->nomor;
            $pembelian->tanggal_referensi = date_format(date_create_from_format('d-m-Y H:i:s', $this->tanggal), 'd-m-Y');
            if (!$pembelian->save()) {
                throw new Exception('Gagal simpan Pembelian');
            }

            /* Insert semua yang ada di po_detail ke pembelian_detail */
            $sql = '
            INSERT INTO pembelian_detail (pembelian_id, barang_id, qty, harga_beli, harga_jual, updated_by, created_at)
            SELECT
                :pembelianId,
                po_detail.barang_id,
                po_detail.qty_order,
                po_detail.harga_beli_terakhir,
                hj.harga,
                :userId,
                NOW()
            FROM
                po_detail
                    JOIN
                (SELECT
                    po_detail.barang_id,
                        MAX(barang_harga_jual.id) max_hj
                FROM
                    po_detail
                JOIN barang_harga_jual ON po_detail.barang_id = barang_harga_jual.barang_id
                WHERE
                    po_detail.po_id = :poId
                GROUP BY po_detail.barang_id) AS tabel_max_id ON tabel_max_id.barang_id = po_detail.barang_id
                    JOIN
                barang_harga_jual hj ON hj.id = tabel_max_id.max_hj
            WHERE
                po_detail.po_id = :poId
                    ';
            $command = Yii::app()->db->createCommand($sql);
            $command->bindValues([
                ':pembelianId' => $pembelian->id,
                ':poId'        => $this->id,
                ':userId'      => Yii::app()->user->id
            ]);
            $rows = $command->execute();

            $transaction->commit();
            return [
                'sukses' => true,
                'data'   => [
                    'pembelianId' => $pembelian->id,
                    'rows'        => $rows
                ]
            ];
        } catch (Exception $ex) {
            $transaction->rollback();
            return [
                'sukses' => false,
                'error'  => [
                    'msg'  => $ex->getMessage(),
                    'code' => $ex->getCode(),
            ]];
        }
    }
}