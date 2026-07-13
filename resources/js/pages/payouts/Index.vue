<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Search,
    Plus,
    Edit,
    Trash2,
    ChevronUp,
    ChevronDown,
    ChevronsUpDown,
    Receipt,
} from '@lucide/vue';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Payouts', href: '/payouts' },
        ],
    },
});

interface Employee {
    id: number;
    name: string;
}

interface Payout {
    id: number;
    task: string;
    amount: string;
    formatted_amount: string;
    status: 'pending' | 'processing' | 'completed';
    created_at: string;
    employee: Employee;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
}

const props = defineProps<{
    payouts: Paginated<Payout>;
    employees: Employee[];
    filters: {
        search: string;
        status: string;
        sort_by: string;
        sort_dir: string;
    };
    payoutCan: {
        create: boolean;
        edit: boolean;
        delete: boolean;
        changeStatus: boolean;
    };
}>();

const search = ref(props.filters.search ?? '');
const filterStatus = ref(props.filters.status ?? '');
const sortBy = ref(props.filters.sort_by ?? 'created_at');
const sortDir = ref(props.filters.sort_dir ?? 'desc');
const deletingId = ref<number | null>(null);

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingPayout = ref<Payout | null>(null);

const createForm = useForm({
    employee_id: '' as unknown as number,
    task: '',
    amount: '' as unknown as number,
    status: 'pending' as string,
});

const editForm = useForm({
    employee_id: '' as unknown as number,
    task: '',
    amount: '' as unknown as number,
    status: 'pending' as string,
});

function applyFilters() {
    router.get(
        '/payouts',
        {
            search: search.value,
            status: filterStatus.value,
            sort_by: sortBy.value,
            sort_dir: sortDir.value,
        },
        { preserveState: true, replace: true },
    );
}

let searchTimer: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 300);
});

watch(filterStatus, applyFilters);

function sortBy_(column: string) {
    if (sortBy.value === column) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function sortIcon(column: string) {
    if (sortBy.value !== column) return ChevronsUpDown;
    return sortDir.value === 'asc' ? ChevronUp : ChevronDown;
}

function openCreateModal() {
    createForm.reset();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
    createForm.reset();
}

function openEditModal(payout: Payout) {
    editingPayout.value = payout;
    editForm.employee_id = payout.employee.id;
    editForm.task = payout.task;
    editForm.amount = parseFloat(payout.amount);
    editForm.status = payout.status;
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingPayout.value = null;
    editForm.reset();
}

function storePayout() {
    createForm.post('/payouts', {
        onSuccess: () => closeCreateModal(),
    });
}

function updatePayout() {
    if (!editingPayout.value) return;
    editForm.put(`/payouts/${editingPayout.value.id}`, {
        onSuccess: () => closeEditModal(),
    });
}

function changeStatus(payout: Payout, status: string) {
    router.patch(`/payouts/${payout.id}/status`, { status });
}

async function deletePayout(id: number) {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This payout will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
    });

    if (!result.isConfirmed) return;

    deletingId.value = id;
    router.delete(`/payouts/${id}`, {
        onFinish: () => { deletingId.value = null; },
    });
}

const statusVariant = (status: string) => {
    if (status === 'completed') return 'default';
    if (status === 'processing') return 'secondary';
    return 'outline';
};

const statusLabel = (status: string) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};
</script>

<template>
    <Head title="Payouts" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <!-- Header -->
        <div class="flex items-center justify-between gap-4">
            <Heading title="Payouts" description="Manage employee payouts" />
            <div class="flex items-center gap-3">
                <!-- Search -->
                <div class="relative hidden sm:block">
                    <Input
                        id="search"
                        v-model="search"
                        placeholder="Search by task..."
                        class="w-64 pl-9"
                    />
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground">
                        <Search class="h-4 w-4" />
                    </span>
                </div>

                <!-- Status filter -->
                <Select v-model="filterStatus">
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All statuses</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="processing">Processing</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Add Payout -->
                <Button v-if="payoutCan.create" @click="openCreateModal" data-test="create-payout-button">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Payout
                </Button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Employee</th>
                        <th
                            class="cursor-pointer px-4 py-3 text-left font-medium"
                            @click="sortBy_('task')"
                        >
                            <span class="inline-flex items-center gap-1">
                                Task
                                <component :is="sortIcon('task')" class="h-3.5 w-3.5" />
                            </span>
                        </th>
                        <th
                            class="cursor-pointer px-4 py-3 text-left font-medium"
                            @click="sortBy_('amount')"
                        >
                            <span class="inline-flex items-center gap-1">
                                Amount
                                <component :is="sortIcon('amount')" class="h-3.5 w-3.5" />
                            </span>
                        </th>
                        <th
                            class="cursor-pointer px-4 py-3 text-left font-medium"
                            @click="sortBy_('status')"
                        >
                            <span class="inline-flex items-center gap-1">
                                Status
                                <component :is="sortIcon('status')" class="h-3.5 w-3.5" />
                            </span>
                        </th>
                        <th
                            class="cursor-pointer px-4 py-3 text-left font-medium"
                            @click="sortBy_('created_at')"
                        >
                            <span class="inline-flex items-center gap-1">
                                Date
                                <component :is="sortIcon('created_at')" class="h-3.5 w-3.5" />
                            </span>
                        </th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="payouts.data.length === 0">
                        <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">
                            <div class="flex flex-col items-center gap-2">
                                <Receipt class="h-8 w-8 opacity-30" />
                                No payouts found.
                            </div>
                        </td>
                    </tr>
                    <tr
                        v-for="payout in payouts.data"
                        :key="payout.id"
                        class="transition-colors hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-medium">{{ payout.employee.name }}</td>
                        <td class="px-4 py-3">{{ payout.task }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ payout.formatted_amount }}</td>
                        <td class="px-4 py-3">
                            <!-- Status dropdown for users who can change status -->
                            <Select
                                v-if="payoutCan.changeStatus"
                                :model-value="payout.status"
                                @update:model-value="(val) => changeStatus(payout, val)"
                            >
                                <SelectTrigger class="h-7 w-36 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="processing">Processing</SelectItem>
                                    <SelectItem value="completed">Completed</SelectItem>
                                </SelectContent>
                            </Select>
                            <!-- Read-only badge for staff -->
                            <Badge v-else :variant="statusVariant(payout.status)" class="capitalize">
                                {{ statusLabel(payout.status) }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ payout.created_at }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    v-if="payoutCan.edit"
                                    variant="outline"
                                    size="sm"
                                    @click="openEditModal(payout)"
                                    :data-test="`edit-payout-${payout.id}`"
                                >
                                    <Edit class="h-3 w-3 mr-1" />
                                    Edit
                                </Button>
                                <Button
                                    v-if="payoutCan.delete"
                                    variant="destructive"
                                    size="sm"
                                    :disabled="deletingId === payout.id"
                                    :data-test="`delete-payout-${payout.id}`"
                                    @click="deletePayout(payout.id)"
                                >
                                    <Trash2 v-if="deletingId !== payout.id" class="h-3 w-3 mr-1" />
                                    <Spinner v-else class="h-3 w-3 mr-1" />
                                    {{ deletingId === payout.id ? 'Deleting…' : 'Delete' }}
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="payouts.last_page > 1" class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Showing {{ payouts.from }}–{{ payouts.to }} of {{ payouts.total }}</span>
            <div class="flex items-center gap-1">
                <template v-for="link in payouts.links" :key="link.label">
                    <Button
                        v-if="link.url"
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                        @click="router.visit(link.url)"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="px-2 py-1 opacity-40"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>

    <!-- Create Payout Modal -->
    <Dialog :open="showCreateModal" @update:open="showCreateModal = $event">
        <DialogContent class="sm:max-w-[480px]">
            <DialogHeader>
                <DialogTitle>Add Payout</DialogTitle>
                <DialogDescription>Create a new payout record for an employee.</DialogDescription>
            </DialogHeader>
            <form @submit.prevent="storePayout" class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="create-employee">Employee</Label>
                    <Select v-model="createForm.employee_id">
                        <SelectTrigger id="create-employee" class="w-full">
                            <SelectValue placeholder="Select employee" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="emp in employees"
                                :key="emp.id"
                                :value="emp.id"
                            >
                                {{ emp.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="createForm.errors.employee_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="create-task">Task</Label>
                    <Input
                        id="create-task"
                        v-model="createForm.task"
                        type="text"
                        placeholder="e.g. Website design"
                        autofocus
                    />
                    <InputError :message="createForm.errors.task" />
                </div>
                <div class="grid gap-2">
                    <Label for="create-amount">Amount (IQD)</Label>
                    <Input
                        id="create-amount"
                        v-model="createForm.amount"
                        type="number"
                        min="0"
                        step="1"
                        placeholder="0"
                    />
                    <InputError :message="createForm.errors.amount" />
                </div>
                <div class="grid gap-2">
                    <Label for="create-status">Status</Label>
                    <Select v-model="createForm.status">
                        <SelectTrigger id="create-status" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="processing">Processing</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="createForm.errors.status" />
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeCreateModal">Cancel</Button>
                    <Button type="submit" :disabled="createForm.processing" data-test="create-payout-submit">
                        <Spinner v-if="createForm.processing" class="mr-2 h-4 w-4" />
                        Create Payout
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Edit Payout Modal -->
    <Dialog :open="showEditModal" @update:open="showEditModal = $event">
        <DialogContent class="sm:max-w-[480px]">
            <DialogHeader>
                <DialogTitle>Edit Payout</DialogTitle>
                <DialogDescription>Update payout details. Click save when you're done.</DialogDescription>
            </DialogHeader>
            <form @submit.prevent="updatePayout" class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="edit-employee">Employee</Label>
                    <Select v-model="editForm.employee_id">
                        <SelectTrigger id="edit-employee" class="w-full">
                            <SelectValue placeholder="Select employee" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="emp in employees"
                                :key="emp.id"
                                :value="emp.id"
                            >
                                {{ emp.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="editForm.errors.employee_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="edit-task">Task</Label>
                    <Input
                        id="edit-task"
                        v-model="editForm.task"
                        type="text"
                        placeholder="e.g. Website design"
                    />
                    <InputError :message="editForm.errors.task" />
                </div>
                <div class="grid gap-2">
                    <Label for="edit-amount">Amount (IQD)</Label>
                    <Input
                        id="edit-amount"
                        v-model="editForm.amount"
                        type="number"
                        min="0"
                        step="1"
                        placeholder="0"
                    />
                    <InputError :message="editForm.errors.amount" />
                </div>
                <div class="grid gap-2">
                    <Label for="edit-status">Status</Label>
                    <Select v-model="editForm.status">
                        <SelectTrigger id="edit-status" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="processing">Processing</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="editForm.errors.status" />
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeEditModal">Cancel</Button>
                    <Button type="submit" :disabled="editForm.processing" data-test="update-payout-submit">
                        <Spinner v-if="editForm.processing" class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>