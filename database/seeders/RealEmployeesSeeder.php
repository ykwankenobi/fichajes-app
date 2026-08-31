<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RealEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->employees() as $employee) {
            $email = $this->emailFor($employee['name']);

            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $employee['name'],
                    'password' => Hash::make(Str::random(32)),
                    'dni' => null,
                    'activo' => $employee['hours'] > 0,
                    'horas_semanales' => $employee['hours'],
                    'puesto' => $employee['position'],
                    'horario' => $employee['schedule'] ?: null,
                    'observaciones' => $employee['notes'] ?: null,
                    'fecha_alta' => null,
                    'fecha_baja' => null,
                    'is_admin' => false,
                ],
            );
        }
    }

    private function emailFor(string $name): string
    {
        return Str::slug($name, '.') . '@fichajes.local';
    }

    /**
     * @return array<int, array{name: string, hours: int, position: string, schedule: string, notes: string}>
     */
    private function employees(): array
    {
        return [
            [
                'name' => 'MARIA DOLORES LAZARO GARCIA',
                'hours' => 40,
                'position' => 'MAÑANAS ADMINISTRACIÓN / TARDES RECEPCIÓN',
                'schedule' => 'Semana A: Lunes 09:00-13:00 y 15:00-19:00; Martes 09:00-13:00 y 16:00-20:00; Miércoles 09:00-13:00 y 15:00-19:00; Jueves 09:00-13:00 y 15:00-19:00; Viernes 09:00-13:00 y 15:00-20:00. Semana B: Lunes 09:00-13:00 y 15:00-20:00; Martes 09:00-13:00 y 16:00-20:00; Miércoles 09:00-13:00 y 15:00-20:00; Jueves 09:00-13:00 y 15:00-19:00; Viernes 09:00-15:00',
                'notes' => 'Horario alterno por semanas',
            ],
            [
                'name' => 'MONICA GARCIA GOMEZ',
                'hours' => 25,
                'position' => 'RECEPCIÓN',
                'schedule' => 'Lunes 09:00-14:00; Martes 15:00-20:00; Miércoles 09:00-14:00; Jueves 09:00-14:00; Viernes 09:00-14:00',
                'notes' => '',
            ],
            [
                'name' => 'PAULA VALIENTE NARRO',
                'hours' => 29,
                'position' => 'RECEPCIÓN',
                'schedule' => 'Lunes 09:00-15:00; Martes 09:00-15:00; Miércoles 14:00-20:00; Jueves 09:00-15:00; Viernes 09:00-14:00',
                'notes' => '',
            ],
            [
                'name' => 'CRISTINA VILLALVA CALVO',
                'hours' => 15,
                'position' => 'RECEPCIÓN',
                'schedule' => 'Lunes 16:00-20:00; Martes 17:00-20:00; Miércoles 09:00-13:00; Jueves 16:00-20:00',
                'notes' => '',
            ],
            [
                'name' => 'CARIDAD MARTINEZ SANCHEZ',
                'hours' => 40,
                'position' => 'RECEPCIÓN',
                'schedule' => 'Lunes 09:00-14:00 y 16:00-20:00; Martes 09:00-17:00; Miércoles 09:00-14:00 y 16:00-20:00; Jueves 09:00-14:00 y 16:00-20:00',
                'notes' => '',
            ],
            [
                'name' => 'ALEJANDRA ATINEZAR ARRIAGA',
                'hours' => 20,
                'position' => 'RECEPCIÓN',
                'schedule' => 'Semana A: Lunes a viernes 09:00-13:00. Semana B: Lunes a miércoles 09:00-13:00; Jueves 09:00-12:00; Viernes 15:00-20:00',
                'notes' => 'Horario alterno por semanas',
            ],
            [
                'name' => 'AINOHA VILLANUEVA ESTEVEZ',
                'hours' => 25,
                'position' => 'ADMINISTRACIÓN / GERENTE',
                'schedule' => 'Lunes a viernes 09:00-14:00',
                'notes' => 'Puesto pendiente de confirmar',
            ],
            [
                'name' => 'MARIA DEL MAR PEREZ GRANADOS',
                'hours' => 25,
                'position' => 'UNIDAD DE TRÁFICO',
                'schedule' => 'Lunes a viernes 09:00-14:00',
                'notes' => '',
            ],
            [
                'name' => 'MARIA GARCIA GOMEZ',
                'hours' => 16,
                'position' => 'ENFERMERA',
                'schedule' => 'Según semana y necesidad de empresa',
                'notes' => 'Horario variable',
            ],
            [
                'name' => 'EILEN KARIN AESPINEL ANGARITA',
                'hours' => 0,
                'position' => 'AUXILIAR ENFERMERÍA',
                'schedule' => '',
                'notes' => 'Excedencia 4 meses desde abril',
            ],
            [
                'name' => 'VALERIA MARTINEZ GIMENEZ',
                'hours' => 30,
                'position' => 'AUXILIAR ENFERMERÍA',
                'schedule' => 'Lunes 09:00-18:00; Martes 09:00-13:00 y 15:30-19:30; Miércoles 09:00-18:00; Jueves 09:00-13:00',
                'notes' => '',
            ],
            [
                'name' => 'IRENE CERES GOMEZ GOMEZ',
                'hours' => 12,
                'position' => 'ENFERMERA',
                'schedule' => 'Según semana y necesidad de empresa',
                'notes' => 'Horario variable',
            ],
            [
                'name' => 'BORJA SEPULVEDA MAESTRO',
                'hours' => 3,
                'position' => 'ENFERMERO',
                'schedule' => 'Ayudante quirófano Franchi',
                'notes' => '',
            ],
            [
                'name' => 'ALVARO VERDEJO ARNEDO',
                'hours' => 6,
                'position' => 'READAPTADOR',
                'schedule' => 'Martes 16:00-20:00; Jueves 16:00-20:00',
                'notes' => 'Cobra a razón de pacientes',
            ],
            [
                'name' => 'JOSE LUIS IBAÑEZ MANCEBO',
                'hours' => 8,
                'position' => 'READAPTADOR',
                'schedule' => 'Lunes 16:00-20:00; Miércoles 16:00-20:00',
                'notes' => 'Cobra a razón de pacientes',
            ],
            [
                'name' => 'NEREA BLASCO GARCIA',
                'hours' => 29,
                'position' => 'FISIOTERAPIA',
                'schedule' => 'Lunes 15:00-20:00; Martes 09:00-14:00; Miércoles 15:00-20:00; Jueves 09:00-13:30 y 16:00-20:00; Viernes 09:00-14:30',
                'notes' => '',
            ],
            [
                'name' => 'ANA NAVARRO LUJAN',
                'hours' => 40,
                'position' => 'FISIOTERAPIA',
                'schedule' => 'Lunes 09:00-17:00; Martes 09:00-13:00 y 16:00-20:00; Miércoles 09:00-17:00; Jueves 09:00-13:00 y 16:00-20:00; Viernes 09:00-17:00',
                'notes' => '',
            ],
            [
                'name' => 'JUAN ABAD SANCHEZ',
                'hours' => 12,
                'position' => 'FISIOTERAPIA',
                'schedule' => 'Lunes 09:00-13:30; Jueves 09:00-13:30',
                'notes' => 'Cobra a razón de pacientes',
            ],
            [
                'name' => 'MARIA CARMEN PARDO JIMENEZ',
                'hours' => 40,
                'position' => 'HIGIENISTA',
                'schedule' => 'Lunes 09:00-18:00; Martes 09:00-18:00; Miércoles 09:00-13:00 y 16:00-20:00; Jueves 09:00-18:00; Viernes 09:00-14:00',
                'notes' => '',
            ],
            [
                'name' => 'NEREA BERBELL',
                'hours' => 26,
                'position' => 'ODONTÓLOGA',
                'schedule' => 'Lunes a jueves 09:00-17:00/17:30',
                'notes' => 'Cobra a razón de pacientes',
            ],
        ];
    }
}
