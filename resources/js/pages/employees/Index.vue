<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Search, Plus, Edit, Trash2 } from '@lucide/vue';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { computed, ref, watch } from 'vue';
import EmployeeController from '@/actions/App/Http/Controllers/EmployeeController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { index as employeesIndex } from '@/routes/employees';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Employees',
                href: employeesIndex.url(),
            },
        ],
    },
});

const page = usePage();
const can = computed(() => (page.props as any).can ?? {});

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
    employees: Paginated<{
        id: number;
        name: string;
        created_at: string;
    }>;
    filters: {
        search?: string;
    };
}>();


const search = ref(props.filters.search ?? '');
const deletingId = ref<number | null>(null);

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingEmployee = ref<{ id: number; name: string } | null>(null);

// Forms
const createForm = useForm({
    name: '',
});

const editForm = useForm({
    name: '',
});

watch(search, (value) => {
    router.get(employeesIndex.url(), { search: value }, {
        preserveState: true,
        replace: true,
    });
});

function openCreateModal() {
    createForm.reset();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
    createForm.reset();
}

function openEditModal(employee: { id: number; name: string }) {
    editingEmployee.value = employee;
    editForm.name = employee.name;
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingEmployee.value = null;
    editForm.reset();
}

function createEmployee() {
    createForm.post(EmployeeController.store.url(), {
        onSuccess: () => {
            closeCreateModal();
        },
    });
}

function updateEmployee() {
    if (!editingEmployee.value) return;
    
    editForm.put(EmployeeController.update.url({ employee: editingEmployee.value.id }), {
        onSuccess: () => {
            closeEditModal();
        },
    });
}

async function deleteEmployee(id: number): Promise<void> {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone. The employee will be permanently removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
    });

    if (!result.isConfirmed) {
        return;
    }

    deletingId.value = id;

    router.delete(EmployeeController.destroy.url({ employee: id }), {
        onFinish: () => {
            deletingId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Employees" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading title="Employees" description="Manage employees" />
            <div class="flex items-center gap-3">
                <div class="relative hidden sm:block">
                    <Input
                        id="search"
                        v-model="search"
                        placeholder="Search employees..."
                        class="w-64 pl-9"
                    />
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground">
                        <Search class="h-4 w-4" />
                    </span>
                </div>
                <Button v-if="can.manage_employees" @click="openCreateModal" data-test="create-employee-button">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Employee
                </Button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Joined</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="employees.data.length === 0">
                        <td colspan="3" class="px-4 py-10 text-center text-muted-foreground">
                            No employees found. Create the first one!
                        </td>
                    </tr>
                    <tr
                        v-for="employee in employees.data"
                        :key="employee.id"
                        class="transition-colors hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-medium">{{ employee.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ employee.created_at }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    v-if="can.manage_employees"
                                    variant="outline"
                                    size="sm"
                                    @click="openEditModal(employee)"
                                    :data-test="`edit-employee-${employee.id}`"
                                >
                                    <Edit class="h-3 w-3 mr-1" />
                                    Edit
                                </Button>
                                <Button
                                    v-if="can.manage_employees"
                                    variant="destructive"
                                    size="sm"
                                    :disabled="deletingId === employee.id"
                                    :data-test="`delete-employee-${employee.id}`"
                                    @click="deleteEmployee(employee.id)"
                                >
                                    <Trash2 v-if="deletingId !== employee.id" class="h-3 w-3 mr-1" />
                                    <Spinner v-else class="h-3 w-3 mr-1" />
                                    {{ deletingId === employee.id ? 'Deleting…' : 'Delete' }}
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="employees.last_page > 1" class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Showing {{ employees.from }}–{{ employees.to }} of {{ employees.total }}</span>
            <div class="flex items-center gap-1">
                <template v-for="link in employees.links" :key="link.label">
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

    <!-- Create Employee Modal -->
    <Dialog :open="showCreateModal" @update:open="showCreateModal = $event">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Add Employee</DialogTitle>
                <DialogDescription>
                    Create a new employee. Click save when you're done.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="createEmployee" class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="create-name">Full Name</Label>
                    <Input
                        id="create-name"
                        v-model="createForm.name"
                        type="text"
                        placeholder="John Doe"
                        required
                        autofocus
                    />
                    <InputError :message="createForm.errors.name" />
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeCreateModal">
                        Cancel
                    </Button>
                    <Button 
                        type="submit" 
                        :disabled="createForm.processing"
                        data-test="create-employee-submit"
                    >
                        <Spinner v-if="createForm.processing" class="mr-2 h-4 w-4" />
                        Create Employee
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Edit Employee Modal -->
    <Dialog :open="showEditModal" @update:open="showEditModal = $event">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Edit Employee</DialogTitle>
                <DialogDescription>
                    Update employee information. Click save when you're done.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="updateEmployee" class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="edit-name">Full Name</Label>
                    <Input
                        id="edit-name"
                        v-model="editForm.name"
                        type="text"
                        placeholder="John Doe"
                        required
                        autofocus
                    />
                    <InputError :message="editForm.errors.name" />
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeEditModal">
                        Cancel
                    </Button>
                    <Button 
                        type="submit" 
                        :disabled="editForm.processing"
                        data-test="update-employee-submit"
                    >
                        <Spinner v-if="editForm.processing" class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
