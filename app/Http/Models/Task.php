<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "Task"}, @SWG\Xml(name="Task"))
 */
class Task extends DBManModel
{
    use Auditable;
    /**
     * @SWG\Property(example=12)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="TASK_ADD_FIXED_IP")
     * @var string
     */
    public $task;

    /**
     * @SWG\Property(example="1234")
     * @var string
     */
    public $data1;

    /**
     * @SWG\Property(example="10.32.54.231")
     * @var string
     */
    public $data2;

    /**
     * @SWG\Property(example="ABC-12")
     * @var string
     */
    public $data3;

    /**
     * @SWG\Property(example="ABC-12")
     * @var string
     */
    public $createdOn;

    /**
     * @SWG\Property(example="ABC-12")
     * @var string
     */
    public $status;

    /**
     * @SWG\Property(example="ABC-12")
     * @var string
     */
    public $completedOn;

    /**
     * @SWG\Property(example="Task completed successfully")
     * @var string
     */
    public $message;

    use Eloquence;
    use Mappable;

    protected $table = 'task';
    protected $primaryKey = 'Idx';
    public $timestamps = false;

    protected $maps = [
        'id' => 'Idx',
        'task' => 'Task',
        'data1' => 'Data1',
        'data2' => 'Data2',
        'data3' => 'Data3',
        'createdOn' => 'Entry_Date',
        'firstValidOn' => 'First_Valid',
        'status' => 'Status',
        'completedOn' => 'Finish_Date',
        'message' => 'Message'
    ];

    protected $fillable = ['id', 'task', 'data1', 'data2', 'data3', 'createdOn', 'firstValidOn', 'status',
                            'completedOn', 'message'];
}
