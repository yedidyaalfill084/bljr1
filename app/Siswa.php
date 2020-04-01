<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $table = 'siswa';
    protected $fillable = ['nama_belakang','nama_depan','jenis_kelamin','agama','alamat','avatar','user_id'];

    public function getAvatar()
    {
        if(!$this->avatar)
        { 
            return asset('admin/assets/img/avatar.png');
        }

        return asset('admin/assets/img/'.$this->avatar);
    }

    public function mapel()
    {
        return $this->belongsToMany(Mapel::class)->withPivot(['nilai'])->withTimeStamps();
    }

    public function rataRataNilai()
    {
        //ambil nilai
        $total = 0;
        $hitung = 0;

        if($this->mapel->isNotEmpty())
        {
            foreach($this->mapel as $mapel)
            {
                $total += $mapel->pivot->nilai;
                $hitung++;
            }
            return round($total/$hitung);
        }
        return 0;
    }

    public function nama_lengkap()
    {
        return $this->nama_depan.' '.$this->nama_belakang;
    }
}
