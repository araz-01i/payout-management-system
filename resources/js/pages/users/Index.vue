<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Search, Plus, Edit, Trash2 } from '@lucide/vue';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { computed, ref, watch } from 'vue';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { index as usersIndex } from '@/routes/users';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: usersIndex.url(),
            },
        ],
    },
});

const page = usePage();

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
    users: Paginated<{
        id: number;
        name: string;
        email: string;
        role: string;
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
const editingUser = ref<{ id: number; name: string; email: string; role: string } | null>(null);

// Forms
const createForm = useForm({
    name: '',
    email: '',
    role: 'staff',
    password: '',
    password_confirmation: '',
});

const editForm = useForm({
    name: '',
    email: '',
    role: 'staff',
    password: '',
    password_confirmation: '',
});

watch(search, (value) => {
    router.get(usersIndex.url(), { search: value }, {
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

function openEditModal(user: { id: number; name: string; email: string; role: string }) {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role = user.role;
    editForm.password = '';
    editForm.password_confirmation = '';
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingUser.value = null;
    editForm.reset();
}

function createUser() {
    createForm.post(UserController.store.url(), {
        onSuccess: () => {
            closeCreateModal();
        },
    });
}

function updateUser() {
    if (!editingUser.value) return;
    
    editForm.patch(UserController.update.url({ user: editingUser.value.id }), {
        onSuccess: () => {
            closeEditModal();
        },
    });
}

async function deleteUser(id: number): Promise<void> {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone. The user will be permanently removed.',
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

    router.delete(UserController.destroy.url({ user: id }), {
        onFinish: () => {
            deletingId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Users" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading title="Users" description="Manage system users and their roles" />
            <div class="flex items-center gap-3">
                <div class="relative hidden sm:block">
                    <Input
                        id="search"
                        v-model="search"
                        placeholder="Search users..."
                        class="w-64 pl-9"
                    />
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground">
                        <Search class="h-4 w-4" />
                    </span>
                </div>
                <Button @click="openCreateModal" data-test="create-user-button">
                    <Plus class="mr-2 h-4 w-4" />
                    Add User
                </Button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Email</th>
                        <th class="px-4 py-3 text-left font-medium">Role</th>
                        <th class="px-4 py-3 text-left font-medium">Joined</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="users.data.length === 0">
                        <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">
                            No users found.
                        </td>
                    </tr>
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="transition-colors hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="{
                                'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400': user.role === 'admin',
                                'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': user.role === 'staff'
                            }">
                                {{ user.role }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ user.created_at }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="openEditModal(user)"
                                    :data-test="`edit-user-${user.id}`"
                                >
                                    <Edit class="h-3 w-3 mr-1" />
                                    Edit
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    :disabled="deletingId === user.id"
                                    :data-test="`delete-user-${user.id}`"
                                    @click="deleteUser(user.id)"
                                >
                                    <Trash2 v-if="deletingId !== user.id" class="h-3 w-3 mr-1" />
                                    <Spinner v-else class="h-3 w-3 mr-1" />
                                    {{ deletingId === user.id ? 'Deleting…' : 'Delete' }}
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="users.last_page > 1" class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Showing {{ users.from }}–{{ users.to }} of {{ users.total }}</span>
            <div class="flex items-center gap-1">
                <template v-for="link in users.links" :key="link.label">
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

    <!-- Create User Modal -->
    <Dialog :open="showCreateModal" @update:open="showCreateModal = $event">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Add User</DialogTitle>
                <DialogDescription>
                    Create a new user account. Click save when you're done.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="createUser" class="grid gap-4 py-4">
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
                <div class="grid gap-2">
                    <Label for="create-email">Email</Label>
                    <Input
                        id="create-email"
                        v-model="createForm.email"
                        type="email"
                        placeholder="john@example.com"
                        required
                    />
                    <InputError :message="createForm.errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="create-role">Role</Label>
                    <Select v-model="createForm.role">
                        <SelectTrigger id="create-role" class="w-full">
                            <SelectValue placeholder="Select a role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="staff">Staff</SelectItem>
                            <SelectItem value="admin">Admin</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="createForm.errors.role" />
                </div>
                <div class="grid gap-2">
                    <Label for="create-password">Password</Label>
                    <Input
                        id="create-password"
                        v-model="createForm.password"
                        type="password"
                        required
                    />
                    <InputError :message="createForm.errors.password" />
                </div>
                <div class="grid gap-2">
                    <Label for="create-password-confirmation">Confirm Password</Label>
                    <Input
                        id="create-password-confirmation"
                        v-model="createForm.password_confirmation"
                        type="password"
                        required
                    />
                    <InputError :message="createForm.errors.password_confirmation" />
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeCreateModal">
                        Cancel
                    </Button>
                    <Button 
                        type="submit" 
                        :disabled="createForm.processing"
                        data-test="create-user-submit"
                    >
                        <Spinner v-if="createForm.processing" class="mr-2 h-4 w-4" />
                        Create User
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Edit User Modal -->
    <Dialog :open="showEditModal" @update:open="showEditModal = $event">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Edit User</DialogTitle>
                <DialogDescription>
                    Update user information. Click save when you're done.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="updateUser" class="grid gap-4 py-4">
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
                <div class="grid gap-2">
                    <Label for="edit-email">Email</Label>
                    <Input
                        id="edit-email"
                        v-model="editForm.email"
                        type="email"
                        placeholder="john@example.com"
                        required
                    />
                    <InputError :message="editForm.errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="edit-role">Role</Label>
                    <Select v-model="editForm.role">
                        <SelectTrigger id="edit-role" class="w-full">
                            <SelectValue placeholder="Select a role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="staff">Staff</SelectItem>
                            <SelectItem value="admin">Admin</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="editForm.errors.role" />
                </div>
                <div class="grid gap-2">
                    <Label for="edit-password">Password (leave blank to keep current)</Label>
                    <Input
                        id="edit-password"
                        v-model="editForm.password"
                        type="password"
                    />
                    <InputError :message="editForm.errors.password" />
                </div>
                <div class="grid gap-2">
                    <Label for="edit-password-confirmation">Confirm Password</Label>
                    <Input
                        id="edit-password-confirmation"
                        v-model="editForm.password_confirmation"
                        type="password"
                    />
                    <InputError :message="editForm.errors.password_confirmation" />
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeEditModal">
                        Cancel
                    </Button>
                    <Button 
                        type="submit" 
                        :disabled="editForm.processing"
                        data-test="update-user-submit"
                    >
                        <Spinner v-if="editForm.processing" class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>