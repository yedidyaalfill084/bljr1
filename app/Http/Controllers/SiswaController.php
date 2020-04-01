<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Exports\SiswaExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use App\Siswa;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        if($request->has('cari'))
        {
            $data_siswa = Siswa::where('nama_depan','LIKE','%'.$request->cari.'%')->get();
        }
        else
        {
            $data_siswa = Siswa::all();
        }
        return view('siswa.index', ['data_siswa' => $data_siswa]);
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'nama_depan' => 'required|min:5',
            'nama_belakang' => 'required',
            'email' => 'required|email|unique:users',
            'jenis_kelamin' => 'required',
            'agama' => 'required',
            'avatar' => 'mimes:jpg,png'
        ]);

        //insert ke table user
        $user = new User;
        $user->role = 'siswa';
        $user->name = $request->nama_depan;
        $user->email = $request->email;
        $user->password = bcrypt('rahasia');
        $user->remember_token = str_random(60);
        $user->save();

        //insert ke table siswa
        $request->request->add(['user_id' => $user->id ]);
        $siswa = Siswa::create($request->all());
        
        if($request->hasFile('avatar'))
        {
            $request->file('avatar')->move('admin/assets/img', $request->file('avatar')->getClientOriginalName());
            $siswa->avatar = $request->file('avatar')->getClientOriginalName();
            $siswa->save();
        }

        return redirect('/siswa')->with('sukses', 'Data Berhasil Di Input');
    }

    public function edit(Siswa $siswa)
    {
        return view('siswa/edit', ['siswa' => $siswa]);
    }

    public function update(Request $request, Siswa $siswa)
    {
        $siswa->update($request->all());
        if($request->hasFile('avatar'))
        {
            $request->file('avatar')->move('admin/assets/img', $request->file('avatar')->getClientOriginalName());
            $siswa->avatar = $request->file('avatar')->getClientOriginalName();
            $siswa->save();
        }
        return redirect('/siswa')->with('sukses', 'Data Berhasil Di Update');
    }

    public function delete(Siswa $siswa)
    {
        $siswa->delete();

        return redirect('/siswa')->with('sukses', 'Data Berhasil dihapus');
    }

    public function profile(Siswa $siswa)
    {
        //$siswa = \App\Siswa::find($id);
        $matapelajaran = \App\Mapel::all();

        //menyiapakan data untuk chart
        $categories = [];
        $data = [];
        foreach($matapelajaran as $mp)
        {
            if($siswa->mapel()->wherePivot('mapel_id',$mp->id)->first())
            {
                $categories[] = $mp->nm_mapel;
                $data[] = $siswa->mapel()->wherePivot('mapel_id',$mp->id)->first()->pivot->nilai;
            }
        }
        //dd($data);

        return view('siswa.profile', ['siswa' => $siswa, 'matapelajaran' => $matapelajaran, 'categories' => $categories, 'data' => $data]);
    }

    public function addnilai(Request $request, Siswa $siswa)
    {
        if($siswa->mapel()->where('mapel_id', $request->mapel)->exists())
        {
            return redirect('siswa/'.$idsiswa.'/profile')->with('error', 'Data Mata Pelajaran Sudah Ada.'); 
        }
        $siswa->mapel()->attach($request->mapel, ['nilai' => $request->nilai]);

        return redirect('siswa/'.$idsiswa.'/profile')->with('sukses', 'Data Nilai Berhasil DiMasukkan');
    }

    public function deletenilai(Siswa $siswa, $idmapel)
    {
        
        $siswa->mapel()->detach($idmapel);

        return redirect()->back()->with('sukses', 'Data Nilai Berhasil Dihapus');
    }

    public function exportExcel() 
    {
        return Excel::download(new SiswaExport, 'siswa.xlsx');
    }

    public function exportPdf()
    {
        $siswa = Siswa::all();
        $pdf = PDF::loadView('export.siswapdf', ['siswa' => $siswa]);
        return $pdf->download('siswa.pdf');
    }

}