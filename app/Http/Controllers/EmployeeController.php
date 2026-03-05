<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function directory()
    {
        // Dummy data for employee directory
        $employees = [
            [
                'id' => 1,
                'name' => 'Juan Dela Cruz',
                'avatar' => 'JC',
                'department' => 'Information Technology',
                'department_code' => 'IT',
                'job_title' => 'Senior Programmer',
                'job_level' => 'Senior Level',
                'status' => 'Active',
                'email' => 'juan.delacruz@medisource.com',
                'phone' => '+63 912 345 6789'
            ],
            [
                'id' => 2,
                'name' => 'Maria Santos',
                'avatar' => 'MS',
                'department' => 'Finance',
                'department_code' => 'FIN',
                'job_title' => 'Financial Analyst',
                'job_level' => 'Mid Level',
                'status' => 'Active',
                'email' => 'maria.santos@medisource.com',
                'phone' => '+63 923 456 7890'
            ],
            [
                'id' => 3,
                'name' => 'Jose Rizal',
                'avatar' => 'JR',
                'department' => 'Nursing',
                'department_code' => 'NRS',
                'job_title' => 'Head Nurse',
                'job_level' => 'Manager',
                'status' => 'Active',
                'email' => 'jose.rizal@medisource.com',
                'phone' => '+63 934 567 8901'
            ],
            [
                'id' => 4,
                'name' => 'Ana Reyes',
                'avatar' => 'AR',
                'department' => 'Human Resources',
                'department_code' => 'HR',
                'job_title' => 'HR Specialist',
                'job_level' => 'Mid Level',
                'status' => 'Active',
                'email' => 'ana.reyes@medisource.com',
                'phone' => '+63 945 678 9012'
            ],
            [
                'id' => 5,
                'name' => 'Pedro Fernandez',
                'avatar' => 'PF',
                'department' => 'Information Technology',
                'department_code' => 'IT',
                'job_title' => 'Network Administrator',
                'job_level' => 'Junior Level',
                'status' => 'Active',
                'email' => 'pedro.fernandez@medisource.com',
                'phone' => '+63 956 789 0123'
            ],
            [
                'id' => 6,
                'name' => 'Luisa Garcia',
                'avatar' => 'LG',
                'department' => 'Finance',
                'department_code' => 'FIN',
                'job_title' => 'Accountant',
                'job_level' => 'Senior Level',
                'status' => 'Active',
                'email' => 'luisa.garcia@medisource.com',
                'phone' => '+63 967 890 1234'
            ],
            [
                'id' => 7,
                'name' => 'Mark Villanueva',
                'avatar' => 'MV',
                'department' => 'Nursing',
                'department_code' => 'NRS',
                'job_title' => 'Staff Nurse',
                'job_level' => 'Junior Level',
                'status' => 'Active',
                'email' => 'mark.villanueva@medisource.com',
                'phone' => '+63 978 901 2345'
            ],
            [
                'id' => 8,
                'name' => 'Sofia Lopez',
                'avatar' => 'SL',
                'department' => 'Administration',
                'department_code' => 'ADM',
                'job_title' => 'Executive Assistant',
                'job_level' => 'Mid Level',
                'status' => 'Active',
                'email' => 'sofia.lopez@medisource.com',
                'phone' => '+63 989 012 3456'
            ],
        ];

        $departments = [
            ['name' => 'Information Technology', 'code' => 'IT', 'count' => 2],
            ['name' => 'Finance', 'code' => 'FIN', 'count' => 2],
            ['name' => 'Nursing', 'code' => 'NRS', 'count' => 2],
            ['name' => 'Human Resources', 'code' => 'HR', 'count' => 1],
            ['name' => 'Administration', 'code' => 'ADM', 'count' => 1],
        ];

        return view('employees.directory', compact('employees', 'departments'));
    }

    public function profile()
    {
        return view('employees.employee-profile');
    }
}