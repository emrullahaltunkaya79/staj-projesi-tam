<?php $__env->startSection('title', 'Kullanıcılar'); ?>

<?php $__env->startSection('actions'); ?>
    <button type="button" class="btn-primary" data-modal-url="<?php echo e(route('kullanicilar.create')); ?>" data-modal-title="Şirkete Kullanıcı Ekle">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Kullanıcı
    </button>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>E-posta</th>
                    <th>Rol</th>
                    <th>Kayıt Tarihi</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="hover:bg-slate-50">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full <?php echo e($user->isAdmin() ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600'); ?> text-xs font-bold">
                                    <?php echo e(mb_strtoupper(mb_substr($user->name, 0, 1))); ?>

                                </div>
                                <div>
                                    <div class="font-medium text-slate-700">
                                        <?php echo e($user->name); ?>

                                        <?php if($user->id === auth()->id()): ?>
                                            <span class="ml-1 text-xs text-slate-400">(siz)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo e($user->email); ?></td>
                        <td>
<?php
    // Kurumsal rol kutusu: tüm satırlarda aynı boyut; renk indigo/slate ailesinden
    $roleBoxClasses = 'h-8 w-36 cursor-pointer rounded-lg border py-0 pl-3 pr-2 text-xs font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500 '
        .($user->isAdmin()
            ? 'border-indigo-200 bg-indigo-50 text-indigo-700'
            : 'border-slate-300 bg-slate-50 text-slate-600');
?>
<?php if($user->id === auth()->id()): ?>
    <select disabled
            title="Kendi rolünüzü değiştiremezsiniz"
            class="<?php echo e($roleBoxClasses); ?> cursor-not-allowed opacity-70">
        <option value="admin" <?php if($user->isAdmin()): echo 'selected'; endif; ?>>Yönetici</option>
        <option value="kullanici" <?php if(! $user->isAdmin()): echo 'selected'; endif; ?>>Kullanıcı</option>
    </select>
<?php else: ?>
    <form method="POST" action="<?php echo e(route('kullanicilar.rol', $user)); ?>" class="inline-block" title="Rolü değiştirmek için listeden seçin">
        <?php echo csrf_field(); ?>
        <select name="role" onchange="this.form.submit()" class="<?php echo e($roleBoxClasses); ?>">
            <option value="admin" <?php if($user->isAdmin()): echo 'selected'; endif; ?>>Yönetici</option>
            <option value="kullanici" <?php if(! $user->isAdmin()): echo 'selected'; endif; ?>>Kullanıcı</option>
        </select>
    </form>
<?php endif; ?>
                        </td>
                        <td><?php echo e($user->created_at->format('d.m.Y')); ?></td>
                        <td>
                            <?php if($user->id !== auth()->id()): ?>
                                <div class="flex justify-end">
                                    <form method="POST" action="<?php echo e(route('kullanicilar.destroy', $user)); ?>"
                                          onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="text-right text-xs text-slate-300">—</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-3 text-xs text-slate-400">
        Yöneticiler kullanıcı ekleyip silebilir ve cari/ürün kayıtlarını silabilir; standart kullanıcılar bu işlemleri yapamaz.
    </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/users/index.blade.php ENDPATH**/ ?>