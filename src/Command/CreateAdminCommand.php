<?php

namespace App\Command;

use App\Entity\Users;
use App\Enum\Role;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user with a properly hashed password (use this instead of inserting in phpMyAdmin)',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UsersRepository $usersRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Admin email')
            ->addArgument('password', InputArgument::OPTIONAL, 'Admin password (min 8 chars, 1 upper, 1 lower, 1 digit)')
            ->addArgument('fullname', InputArgument::OPTIONAL, 'Admin full name')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask for missing values (will fail if any argument is missing)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $fullname = $input->getArgument('fullname');

        $helper = $this->getHelper('question');

        if (!$email) {
            if ($input->getOption('no-interaction')) {
                $io->error('Email is required. Run with email argument or without -n to be prompted.');
                return Command::FAILURE;
            }
            $question = new Question('Admin email: ');
            $question->setValidator(function ($value) {
                if (empty(trim($value ?? ''))) {
                    throw new \RuntimeException('Email cannot be empty.');
                }
                if (!filter_var(trim($value), \FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Invalid email address.');
                }
                return trim($value);
            });
            $email = $helper->ask($input, $output, $question);
        } else {
            $email = trim($email);
            if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
                $io->error('Invalid email address.');
                return Command::FAILURE;
            }
        }

        if ($this->usersRepository->findOneBy(['email' => $email])) {
            $io->error(sprintf('A user with email "%s" already exists.', $email));
            return Command::FAILURE;
        }

        if (!$password) {
            if ($input->getOption('no-interaction')) {
                $io->error('Password is required. Run with password argument or without -n to be prompted.');
                return Command::FAILURE;
            }
            $question = new Question('Password (min 8 chars, 1 upper, 1 lower, 1 digit): ');
            $question->setHidden(true);
            $question->setValidator(function ($value) {
                if (strlen($value ?? '') < 8) {
                    throw new \RuntimeException('Password must be at least 8 characters.');
                }
                if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $value)) {
                    throw new \RuntimeException('Password must contain at least one uppercase, one lowercase and one digit.');
                }
                return $value;
            });
            $password = $helper->ask($input, $output, $question);
        } else {
            if (strlen($password) < 8) {
                $io->error('Password must be at least 8 characters.');
                return Command::FAILURE;
            }
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
                $io->error('Password must contain at least one uppercase, one lowercase and one digit.');
                return Command::FAILURE;
            }
        }

        if (!$fullname) {
            if ($input->getOption('no-interaction')) {
                $io->error('Full name is required. Run with fullname argument or without -n to be prompted.');
                return Command::FAILURE;
            }
            $question = new Question('Full name (min 4 chars): ', 'Admin');
            $question->setValidator(function ($value) {
                $v = trim($value ?? '');
                if (strlen($v) < 4) {
                    throw new \RuntimeException('Full name must be at least 4 characters.');
                }
                return $v;
            });
            $fullname = $helper->ask($input, $output, $question);
        } else {
            $fullname = trim($fullname);
            if (strlen($fullname) < 4) {
                $io->error('Full name must be at least 4 characters.');
                return Command::FAILURE;
            }
        }

        $user = new Users();
        $user->setEmail($email);
        $user->setFullname($fullname);
        $user->setRole(Role::ADMIN);
        $user->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Admin user created: %s (%s). You can log in with this email and password.', $user->getFullname(), $user->getEmail()));

        return Command::SUCCESS;
    }
}
