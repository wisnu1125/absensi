<?php

namespace App\Controllers\Master;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\UserModel;

class AuditLog extends BaseController
{
    public function index()
    {
        $filter = $this->ambilFilter();
        $model  = new AuditLogModel();

        $builder = $model->select('audit_logs.*, users.full_name, users.username')
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->where('audit_logs.created_at >=', $filter['tanggal_dari'] . ' 00:00:00')
            ->where('audit_logs.created_at <=', $filter['tanggal_sampai'] . ' 23:59:59');

        if ($filter['user_id'] !== '') {
            $builder->where('audit_logs.user_id', $filter['user_id']);
        }
        if ($filter['aktivitas'] !== '') {
            $builder->where('audit_logs.aktivitas', $filter['aktivitas']);
        }
        if ($filter['cari'] !== '') {
            $builder->groupStart()
                ->like('audit_logs.keterangan', $filter['cari'])
                ->orLike('users.full_name', $filter['cari'])
                ->groupEnd();
        }

        $rows = $builder->orderBy('audit_logs.created_at', 'DESC')->paginate(25);

        $data = [
            'title'   => 'Audit Log',
            'content' => view('master/audit_log/index', [
                'rows'          => $rows,
                'pager'         => $model->pager,
                'filter'        => $filter,
                'users'         => (new UserModel())->orderBy('full_name', 'ASC')->findAll(),
                'aktivitasList' => $model->getDistinctAktivitas(),
            ]),
        ];

        return view('layouts/main', $data);
    }

    private function ambilFilter(): array
    {
        return [
            'tanggal_dari'   => $this->request->getGet('tanggal_dari') ?: date('Y-m-01'),
            'tanggal_sampai' => $this->request->getGet('tanggal_sampai') ?: date('Y-m-d'),
            'user_id'        => $this->request->getGet('user_id') ?: '',
            'aktivitas'      => $this->request->getGet('aktivitas') ?: '',
            'cari'           => trim((string) $this->request->getGet('cari')),
        ];
    }
}
