<?php

declare(strict_types=1);

use App\Enums\CategorieUtilisateur;
use App\Services\AccessService;
use PHPUnit\Framework\TestCase;

final class RoleAndAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testCanonicalRoleCodesAreStable(): void
    {
        self::assertSame(
            [
                'agent',
                'responsable_directeur',
                'dg',
                'responsable_administratif',
                'responsable_administratif_adjoint',
                'super_admin',
            ],
            array_map(static fn(CategorieUtilisateur $role): string => $role->value, CategorieUtilisateur::cases())
        );
    }

    public function testRequesterCanViewOwnDemande(): void
    {
        $_SESSION['user_id'] = 42;
        $_SESSION['role_code'] = CategorieUtilisateur::AGENT->value;

        self::assertTrue(AccessService::canViewDemande(['user_id' => 42, 'service_id' => 7]));
        self::assertFalse(AccessService::canViewDemande(['user_id' => 43, 'service_id' => 7]));
    }

    public function testAdministrativeRolesCanViewDemande(): void
    {
        $_SESSION['user_id'] = 9;
        $_SESSION['role_code'] = CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value;

        self::assertTrue(AccessService::canViewDemande(['user_id' => 42, 'service_id' => 7]));
    }

    public function testAdministrativeDeputyCanViewDemande(): void
    {
        $_SESSION['user_id'] = 10;
        $_SESSION['role_code'] = CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value;

        self::assertTrue(AccessService::canViewDemande(['user_id' => 42, 'service_id' => 7]));
        self::assertTrue(\App\Core\AuthHelper::isRA());
        self::assertTrue(\App\Core\AuthHelper::isRADeputy());
        self::assertFalse(\App\Core\AuthHelper::isRAChief());
    }

    public function testOnlyManagerialRolesCanBeAssignedToManageServices(): void
    {
        self::assertSame(
            [
                CategorieUtilisateur::RESPONSABLE_DIRECTEUR->value,
                CategorieUtilisateur::DG->value,
                CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value,
                CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value,
            ],
            CategorieUtilisateur::serviceManagerCodes()
        );
        self::assertFalse(CategorieUtilisateur::AGENT->canManageService());
        self::assertFalse(CategorieUtilisateur::SUPER_ADMIN->canManageService());
    }
}
