<x-app-layout title="Product Categories">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Product Categories & Taxonomy</h1>
      <p class="text-muted mb-0">Organize electronic appliances, smartphones, home entertainment, and solar financing lines.</p>
    </div>
    <div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
        <i class="bi bi-plus-circle me-1"></i>New Category
      </button>
    </div>
  </div>

  <div class="row g-4">
    @forelse($categories as $category)
      <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="d-flex align-items-center gap-3">
                <div class="bg-primary-subtle text-primary rounded p-3">
                  <i class="bi {{ $category->icon ?: 'bi-box-seam' }} fs-4"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-0 text-dark">{{ $category->name }}</h5>
                  <small class="text-muted font-monospace">{{ $category->slug }}</small>
                </div>
              </div>
              <span class="badge {{ $category->is_active ? 'bg-success-subtle text-success' : 'bg-secondary' }}">
                {{ $category->is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>

            <p class="text-muted small mb-3">
              {{ $category->description ?: 'No category description provided.' }}
            </p>

            <div class="d-flex justify-content-between align-items-center pt-3 border-top small">
              <div>
                <span class="text-muted">Assigned Products:</span>
                <strong class="text-dark ms-1">{{ $category->products_count }}</strong>
              </div>
              @if($category->parent)
                <div>
                  <span class="text-muted">Parent:</span>
                  <span class="badge bg-light text-dark ms-1">{{ $category->parent->name }}</span>
                </div>
              @endif
              <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
                        data-bs-toggle="modal" data-bs-target="#editModal{{ $category->id }}">
                  <i class="bi bi-pencil"></i>
                </button>
                @if($category->products_count === 0)
                  <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete this category?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal: Edit Category -->
      <div class="modal fade" id="editModal{{ $category->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content text-start">
            <form action="{{ route('categories.update', $category) }}" method="POST">
              @csrf
              @method('PUT')
              <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Category: {{ $category->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Parent Category (Optional)</label>
                  <select name="parent_id" class="form-select">
                    <option value="">None (Top-Level Category)</option>
                    @foreach($parentCategories as $parent)
                      @if($parent->id !== $category->id)
                        <option value="{{ $parent->id }}" {{ $category->parent_id === $parent->id ? 'selected' : '' }}>
                          {{ $parent->name }}
                        </option>
                      @endif
                    @endforeach
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Bootstrap Icon Class</label>
                  <input type="text" name="icon" class="form-control" value="{{ $category->icon ?: 'bi-box-seam' }}">
                  <small class="text-muted">e.g. bi-phone, bi-tv, bi-snow, bi-sun</small>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Description</label>
                  <textarea name="description" class="form-control" rows="2">{{ $category->description }}</textarea>
                </div>
                <div class="form-check">
                  <input type="hidden" name="is_active" value="0">
                  <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActive{{ $category->id }}" {{ $category->is_active ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="editActive{{ $category->id }}">Active in Showroom</label>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-tags fs-1 d-block mb-2 text-secondary"></i>
        No product categories configured yet. Click "New Category" to begin catalog classification.
      </div>
    @endforelse
  </div>

  <!-- Modal: Create Category -->
  <div class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-start">
        <form action="{{ route('categories.store') }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Create Product Category</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Smartphones, Inverter ACs, LED TVs" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Parent Category (Optional)</label>
              <select name="parent_id" class="form-select">
                <option value="">None (Top-Level Category)</option>
                @foreach($parentCategories as $parent)
                  <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Bootstrap Icon Class</label>
              <input type="text" name="icon" class="form-control" value="bi-box-seam">
              <small class="text-muted">e.g. bi-phone, bi-tv, bi-snow, bi-lightning-charge</small>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Description</label>
              <textarea name="description" class="form-control" rows="2" placeholder="Brief description of product line..."></textarea>
            </div>
            <div class="form-check">
              <input type="hidden" name="is_active" value="0">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createActive" checked>
              <label class="form-check-label fw-semibold" for="createActive">Active in Showroom</label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Category</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</x-app-layout>
