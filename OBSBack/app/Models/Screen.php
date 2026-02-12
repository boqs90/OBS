<?php

namespace App\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Screen extends Model
{
    protected $fillable = [
        'key',
        'label',
        'group', // Mantener para compatibilidad temporal
        'section',        // Nivel 1: Sección de menú
        'category',       // Nivel 2: Categoría
        'link_type',      // Nivel 3: Tipo de elemento (section, category, link)
        'parent_id',      // Para estructura jerárquica
        'sort_order',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_screen');
    }

    // Relaciones jerárquicas
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    // Scopes para consultas jerárquicas
    public function scopeSections($query)
    {
        return $query->where('link_type', 'section')->whereNull('parent_id');
    }

    public function scopeCategories($query)
    {
        return $query->where('link_type', 'category');
    }

    public function scopeLinks($query)
    {
        return $query->where('link_type', 'link');
    }

    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Obtener estructura jerárquica completa
    public static function getHierarchy()
    {
        return self::with(['children.children'])
            ->sections()
            ->orderBy('sort_order')
            ->get();
    }
}
