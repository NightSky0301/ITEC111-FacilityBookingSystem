<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Addons;
use App\Models\BookingModel;
use App\Models\FacilityModel;

class Facilities extends BaseController
{
    private const UPLOAD_DIR = 'uploads/facilities';

    private function rules(): array
    {
        return [
            'name'           => 'required|min_length[2]|max_length[120]',
            'type'           => 'required|in_list[' . implode(',', array_keys(FacilityModel::TYPES)) . ']',
            'description'    => 'permit_empty|max_length[2000]',
            'capacity'       => 'required|is_natural_no_zero',
            'price_per_hour' => 'required|decimal',
            'status'         => 'required|in_list[active,inactive]',
            'open_hour'      => 'permit_empty|is_natural|less_than[24]',
            'close_hour'     => 'permit_empty|is_natural_no_zero|less_than_equal_to[24]',
            'new_addons.*.price' => 'permit_empty|decimal',
        ];
    }

    private function payload(): array
    {
        return [
            'name'           => trim($this->request->getPost('name')),
            'type'           => $this->request->getPost('type'),
            'description'    => trim((string) $this->request->getPost('description')),
            'capacity'       => (int) $this->request->getPost('capacity'),
            'price_per_hour' => (float) $this->request->getPost('price_per_hour'),
            'status'         => $this->request->getPost('status'),
            // Blank = the usual hours for this kind of facility.
            'open_hour'      => ($o = $this->request->getPost('open_hour')) === '' || $o === null ? null : (int) $o,
            'close_hour'     => ($c = $this->request->getPost('close_hour')) === '' || $c === null ? null : (int) $c,
        ];
    }

    /**
     * Saves an uploaded photo (jpg, png or webp, up to 3 MB).
     * Returns [relativePath|null, errorMessage|null]. [null, null] means no file was chosen.
     */
    private function saveUpload(): array
    {
        $file = $this->request->getFile('image');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        if (! $file->isValid()) {
            return [null, 'The photo could not be uploaded (' . $file->getErrorString() . '). Try a smaller file.'];
        }
        if ($file->getSize() > 3 * 1024 * 1024) {
            return [null, 'The photo is too large. Please use one under 3 MB.'];
        }

        // Check the real file contents, not just the extension.
        $info = @getimagesize($file->getTempName());
        $ext  = match ($info[2] ?? null) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_WEBP => 'webp',
            default        => null,
        };
        if ($ext === null) {
            return [null, 'The photo must be a JPG, PNG or WEBP image.'];
        }

        $dir = FCPATH . self::UPLOAD_DIR;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        $file->move($dir, $name);

        return [self::UPLOAD_DIR . '/' . $name, null];
    }

    /** Deletes a previously uploaded photo (never touches the built-in illustrations). */
    private function deleteUpload(?string $path): void
    {
        if ($path && str_starts_with($path, self::UPLOAD_DIR . '/') && is_file(FCPATH . $path)) {
            @unlink(FCPATH . $path);
        }
    }

    /** Everything the Add / Edit form needs besides the facility itself. */
    private function formData(?array $facility): array
    {
        $defaults = [];
        foreach (array_keys(FacilityModel::TYPES) as $type) {
            $defaults[$type] = Addons::defaultsFor($type);
        }

        return [
            'facility'     => $facility,
            'types'        => FacilityModel::TYPES,
            'addons'       => Addons::all(),
            'chosenAddons' => $facility ? Addons::keysFor((int) $facility['id']) : [],
            'defaults'     => $defaults,
            'perLabels'    => Addons::PER_LABELS,
        ];
    }

    /** Saves the add-ons ticked on the form, plus any new add-ons typed in, for one facility. */
    private function saveAddons(int $facilityId): void
    {
        $keys = array_values(array_filter((array) $this->request->getPost('addons'), 'is_string'));
        foreach ((array) $this->request->getPost('new_addons') as $n) {
            if (! is_array($n)) {
                continue;
            }
            $key = Addons::create(
                (string) ($n['label'] ?? ''),
                is_numeric($n['price'] ?? null) ? (float) $n['price'] : 0.0,
                (string) ($n['per'] ?? 'session'),
                (string) ($n['group'] ?? 'extra'),
                (string) ($n['note'] ?? ''),
                ! empty($n['evening']),
                ! empty($n['indoor'])
            );
            if ($key !== null) {
                $keys[] = $key;
            }
        }
        Addons::syncFacility($facilityId, $keys);
    }

    public function index()
    {
        return view('admin/facilities/index', [
            'facilities' => (new FacilityModel())->orderBy('type')->orderBy('name')->findAll(),
            'types'      => FacilityModel::TYPES,
        ]);
    }

    public function new()
    {
        return view('admin/facilities/form', $this->formData(null));
    }

    public function create()
    {
        if (! $this->validate($this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        [$path, $error] = $this->saveUpload();
        if ($error) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $data = $this->payload();
        $data['image'] = $path;
        $model = new FacilityModel();
        $model->insert($data);
        $this->saveAddons((int) $model->getInsertID());

        return redirect()->to('/admin/facilities')->with('success', $data['name'] . ' was added.');
    }

    public function edit(int $id)
    {
        $facility = (new FacilityModel())->find($id);
        if (! $facility) {
            return redirect()->to('/admin/facilities')->with('error', 'Facility not found.');
        }
        return view('admin/facilities/form', $this->formData($facility));
    }

    public function update(int $id)
    {
        $model    = new FacilityModel();
        $facility = $model->find($id);
        if (! $facility) {
            return redirect()->to('/admin/facilities')->with('error', 'Facility not found.');
        }
        if (! $this->validate($this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        [$path, $error] = $this->saveUpload();
        if ($error) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $data = $this->payload();
        if ($path !== null) {
            $this->deleteUpload($facility['image']);
            $data['image'] = $path;
        } elseif ($this->request->getPost('remove_image')) {
            $this->deleteUpload($facility['image']);
            $data['image'] = null;   // falls back to the generated illustration
        }

        $model->update($id, $data);
        $this->saveAddons($id);

        return redirect()->to('/admin/facilities')->with('success', $data['name'] . ' was updated.');
    }

    public function archive(int $id)
    {
        $model    = new FacilityModel();
        $facility = $model->find($id);
        if (! $facility) {
            return redirect()->to('/admin/facilities')->with('error', 'Facility not found.');
        }

        // Archived, not erased: booking history stays intact and the admin can restore it from the Archive.
        $model->delete($id);
        return redirect()->to('/admin/facilities')->with('success', $facility['name'] . ' moved to the Archive.');
    }
}
