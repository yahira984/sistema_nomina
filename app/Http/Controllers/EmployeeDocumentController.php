<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Empleado;
use App\Models\EmployeeDocument;
use App\Services\EmployeeDocumentStorageService;
use App\Support\EmployeeDocumentCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

class EmployeeDocumentController extends Controller
{
    public function store(Request $request, Empleado $empleado, EmployeeDocumentStorageService $storage)
    {
        $data = $request->validate([
            'document_type' => ['required', Rule::in(array_keys(EmployeeDocumentCatalog::TYPES))],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:15360'],
        ], [
            'file.required' => 'Selecciona un PDF o una imagen escaneada.',
            'file.mimes' => 'Solo se aceptan PDF, JPG, PNG o WebP.',
            'file.max' => 'El archivo no debe superar 15 MB antes de comprimirse.',
        ]);

        try {
            $document = $storage->store($empleado, $data['document_type'], $request->file('file'), $request->user()?->id);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        AuditLog::record('employee.document_saved', $empleado, [
            'description' => 'Documento del expediente guardado o reemplazado.',
            'metadata' => [
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'label' => EmployeeDocumentCatalog::label($document->document_type),
                'original_size_bytes' => $document->original_size_bytes,
                'stored_size_bytes' => $document->stored_size_bytes,
            ],
        ]);

        return back()->with('success', 'Documento guardado y optimizado correctamente.');
    }

    public function view(Empleado $empleado, EmployeeDocument $document)
    {
        $this->assertOwner($empleado, $document);
        abort_unless(Storage::disk('local')->exists($document->path), 404, 'El archivo ya no existe en almacenamiento.');

        AuditLog::record('employee.document_viewed', $empleado, [
            'description' => 'Documento privado consultado.',
            'metadata' => ['document_id' => $document->id, 'document_type' => $document->document_type],
        ]);

        return response()->file(Storage::disk('local')->path($document->path), [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.$this->downloadName($document).'"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function download(Empleado $empleado, EmployeeDocument $document)
    {
        $this->assertOwner($empleado, $document);
        abort_unless(Storage::disk('local')->exists($document->path), 404, 'El archivo ya no existe en almacenamiento.');

        AuditLog::record('employee.document_downloaded', $empleado, [
            'description' => 'Documento privado descargado.',
            'metadata' => ['document_id' => $document->id, 'document_type' => $document->document_type],
        ]);

        return Storage::disk('local')->download($document->path, $this->downloadName($document), [
            'Content-Type' => $document->mime_type,
        ]);
    }

    public function destroy(Empleado $empleado, EmployeeDocument $document, EmployeeDocumentStorageService $storage)
    {
        $this->assertOwner($empleado, $document);
        $metadata = ['document_id' => $document->id, 'document_type' => $document->document_type];
        $storage->delete($document);

        AuditLog::record('employee.document_deleted', $empleado, [
            'description' => 'Documento privado eliminado del expediente.',
            'metadata' => $metadata,
        ]);

        return back()->with('success', 'Documento eliminado correctamente.');
    }

    private function assertOwner(Empleado $employee, EmployeeDocument $document): void
    {
        abort_unless($document->empleado_id === $employee->id, 404);
    }

    private function downloadName(EmployeeDocument $document): string
    {
        $label = EmployeeDocumentCatalog::label($document->document_type) ?: 'Documento';

        return str($label)->ascii()->slug('_').'.'.$document->extension;
    }
}
