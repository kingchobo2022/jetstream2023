<?php

namespace App\Livewire;

use App\Models\Item;
use Livewire\Component;
use Livewire\WithPagination;

class Items extends Component
{
    use WithPagination;

    public $active = false;
    public $q = '';
    public $selected_item;
    public $item = [];
    public $sort_by = 'id';
    public $sortAsc = true;
    public $confirmingItemDeletion = false;
    public $confirmingItemAdd;

    protected $queryString = [
        'active' => ['keep' => false ],
        'q' => ['keep' => '' ],
        'sort_by' => ['keep' => ''],
        'sortAsc' => ['keep' => false]
    ];

    protected $rules = [
        'item.name' => 'required|string|min:3',
        'item.price' => 'required|numeric|between:1,100',
        'item.status' => 'boolean'
    ];

    public function updatingActive()
    {
        $this->resetPage();
    }

    public function updatingQ()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if($field == $this->sort_by) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortAsc = true;
        }
        $this->sort_by = $field;
    }

    public function confirmItemDeletion($id)
    {
        //$item->delete();
        $this->confirmingItemDeletion = $id;

    }

    public function deleteItem(Item $item)
    {
        $item->delete();
        $this->confirmingItemDeletion = false;
        session()->flash('message', '아이템이 성공적으로 삭제되었습니다.');
    }

    public function confirmAdd()
    {
        $this->reset(['item']);
        $this->confirmingItemAdd = true;
    }

    public function confirmItemEdit(Item $item)
    {
        $this->item['id'] = $item->id;
        $this->item['name'] = $item->name;
        $this->item['price'] = $item->price;
        $this->item['status'] = $item->status == 1 ? true :false;
        $this->confirmingItemAdd = true;
    }

    public function saveItem()
    {
        $this->validate();

        if(isset($this->item['id'])) {
            auth()->user()->items()->where('id', $this->item['id'])->update([
                'name' => $this->item['name'],
                'price' => $this->item['price'],
                'status' => $this->item['status'] ?? 0
            ]);
            session()->flash('message', '아이템이 성공적으로 수정되었습니다.');

        } else {
            auth()->user()->items()->create([
                'name' => $this->item['name'],
                'price' => $this->item['price'],
                'status' => $this->item['status'] ?? 0
            ]);
            session()->flash('message', '아이템이 성공적으로 등록되었습니다.');
        }


        $this->confirmingItemAdd = false;
    }

    public function render()
    {
        $items = Item::where('user_id', auth()->user()->id)
        ->when($this->q, function($query) {
            return $query
                ->where('name', 'like', '%'. $this->q .'%')
                ->orwhere('price', 'like', '%'. $this->q .'%');
        })
        ->when($this->active, function($query) {
            // return $query->where('status', 1);
            return $query->active();
        })
        ->orderBy($this->sort_by, $this->sortAsc ? 'ASC' : 'DESC');

        $query = $items->toSql();            
        $items = $items->paginate(10);

        return view('livewire.items', compact('items', 'query')); // [ 'items' => $items, 'query' => $query ]
    }

    
}
