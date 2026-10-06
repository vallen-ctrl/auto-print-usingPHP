<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TokoService;
use App\Services\PengaturanTokoService;

class tokoControler extends Controller
{
    public function post(Request $request)   // ← tambahkan Request
    {
        $postData = $request->all();          // ← jangan pakai getInput()
        try {
            $uuid = TokoService::insertToko($postData);
            PengaturanTokoService::insertToko($uuid, 0, 0, 4);
            return $this->jsonResponse("succed");   // ← WAJIB pakai return
        } catch (\InvalidArgumentException $e) {
            return $this->jsonResponse($e->getMessage(), [], true, 403);
        } catch (\Exception $e) {
            return $this->jsonResponse($e->getMessage(), [], true, 403);
        }
    }

    public function delete(Request $request)
    {
        $postData = $request->all();
        try {
            verifiedToken();
            return $this->jsonResponse("succed delete", ["succed" => true]);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonResponse($e->getMessage(), [], true, 403);
        } catch (\Exception $e) {
            return $this->jsonResponse($e->getMessage(), [], true, 403);
        }
    }

    public function get()
    {
        try {
            $data = TokoService::getTokoInformation();
            return $this->jsonResponse("succed Get toko", $data);
        } catch (\Exception $e) {
            return $this->jsonResponse($e->getMessage(), [], true, 403);
        }
    }
}