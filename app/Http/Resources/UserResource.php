<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'nisn' => $this->nisn,
            'nomor_wa' => $this->nomor_wa,
            'alamat' => $this->alamat,
            'provinsi_id' => $this->provinsi_id,
            'kabupaten_id' => $this->kabupaten_id,
            'jenjang' => $this->jenjang,
            'kelas' => $this->kelas,
            'asal_sekolah' => $this->asal_sekolah,
            'link_twibbon' => $this->link_twibbon,
            'link_bukti_pembayaran' => $this->link_bukti_pembayaran,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
