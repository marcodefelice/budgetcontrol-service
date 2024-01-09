<?php

namespace App\Budget\Services;

use App\Budget\Domain\Entity\BudgetConfigurator;
use Illuminate\Database\Eloquent\Builder;
use App\Budget\Domain\Model\Budget;
use App\BudgetTracker\Entity\DateTime;
use App\BudgetTracker\Entity\Wallet;
use App\BudgetTracker\Enums\EntryType;
use App\BudgetTracker\Enums\PlanningType;
use App\BudgetTracker\Interfaces\EntryInterface;
use App\BudgetTracker\Models\Account;
use App\BudgetTracker\Models\Entry;
use App\BudgetTracker\Models\Labels;
use App\BudgetTracker\Models\SubCategory;
use App\User\Services\UserService;
use Exception;

class BudgetMamangerService
{

    public function save(array $data): void
    {
        $configuration = $this->configuration($data);

        if (!empty($data['id'])) {
            $budget = Budget::find($data['id']);
        } else {
            $budget = new Budget();
        }

        $budget->budget = $data['amount'];
        $budget->configuration = $configuration->toJson();
        $budget->user_id = UserService::getCacheUserID();
        $budget->save();
    }

    public function retriveBudgetAmount(int $budgetId): array
    {
        $result = [];

        $budget = Budget::User()->where('id',$budgetId)->first();

        if(is_null($budget)) {
            throw new Exception("No budget found", 404);
        }

        $config = json_decode($budget->configuration);
        $entries = $this->getEntires($config);
        $balance = new Wallet();
        foreach($entries as $entry) {
            $balance->deposit($entry->amount);
        }

        $result = [
            'id' => $budget->id,
            'uuid' => $budget->uuid,
            'budget' => $budget->budget,
            'config' => $config,
            'amount' => $balance->getBalance(),
            'percentage' => percentage($balance->getBalance(), $budget->budget),
            'difference' => $budget->budget - $balance->getBalance()
        ];
        
        return $result;
    }

    public function retriveBudgetsAmount(): array
    {
        $result = [];

        $configurations = Budget::User()->get();
        foreach($configurations as $budget) {
            $config = json_decode($budget->configuration);
            $entries = $this->getEntires($config);
            $balance = new Wallet();
            foreach($entries as $entry) {
                $balance->deposit($entry->amount);
            }

            $result[] = [
                'id' => $budget->id,
                'uuid' => $budget->uuid,
                'budget' => $budget->budget,
                'config' => $config,
                'amount' => $balance->getBalance(),
                'percentage' => percentage($balance->getBalance(), $budget->budget),
                'difference' => $budget->budget - $balance->getBalance()
            ];
        }
        
        return $result;

    }

    private function getEntires($config)
    {
            $entries = Entry::User();

            if(!empty($config->account)) {
                $entries->whereIn('account_id',(array) $config->account);
            }

            if(!empty($config->category)) {
                $entries->whereIn('category_id',(array) $config->category);
            }

            if(!empty($config->type)) {
                $entries->whereIn('type',(array) $config->type);
            }

            if(!empty($config->label)) {
                $tags = (array) $config->label;
                $entries->whereHas('label', function (Builder $q) use ($tags) {
                    $q->whereIn('labels.id', $tags);
                });
            }

            //set date to find a entries
            $dateTime = $this->getDate($config->period, $config->start_date, $config->end_date);
            $entries->where('date_time', '>=', $dateTime->startDate);
            $entries->where('date_time', '<=', $dateTime->endDate);

            $entries = $entries->get();

            return $entries;
    }

    private function getDate(string $type, ?string $start = '', ?string $end = ''): DateTime
    {
        switch($type) {
            case PlanningType::Week->value :
                return DateTime::week();
                break;
            case PlanningType::Month->value :
                return DateTime::month();
                break;
            case PlanningType::Year->value :
                return DateTime::year();
                break;
            default:
                return DateTime::custom($start, $end);
                break;
        }

        throw new Exception("No planning is specified", 500);
    }

    /**
     * makeconfiguration
     */
    public function configuration(array $data): BudgetConfigurator
    {
        $configuration = new BudgetConfigurator(
            $data['amount'],
            PlanningType::from($data['period'])
        );

        if (!empty($data['account'])) {
            foreach ($data['account'] as $account) {
                $configuration->setAccount(Account::find($account));
            }
        }

        if (!empty($data['type'])) {
            foreach ($data['type'] as $type) {
                $configuration->setType(EntryType::from($type));
            }
        }

        if (!empty($data['category'])) {
            foreach ($data['category'] as $category) {
                $configuration->setCategory(SubCategory::find($category));
            }
        }

        if (!empty($data['label'])) {
            foreach ($data['label'] as $label) {
                $configuration->setLabel(Labels::find($label));
            }
        }

        if (!empty($data['name'])) {
            $configuration->setName($data['name']);
        }

        if (!empty($data['note'])) {
            $configuration->setNote($data['note']);
        }

        if (!empty($data['start_date'])) {
            $configuration->setStartDate($data['start_date']);
        }

        if (!empty($data['end_date'])) {
            $configuration->setEndDate($data['end_date']);
        }

        return $configuration;
    }

    public function isExpired(int $id): bool
    {
        $entries = $this->retriveBudgetAmount($id);
        if($entries['amount'] >= $entries['budget']) {
            return true;
        }

        return false;
    }

}
