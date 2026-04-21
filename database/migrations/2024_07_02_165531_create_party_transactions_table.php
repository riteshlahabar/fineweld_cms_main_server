<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');

            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            $table->decimal('to_pay', 20, 4)->default(0);
            $table->decimal('to_receive', 20, 4)->default(0);

            // auto creates transaction_id & transaction_type
            $table->morphs('transaction');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');

            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            $table->decimal('to_pay', 20, 4)->default(0);
            $table->decimal('to_receive', 20, 4)->default(0);

            // auto creates transaction_id & transaction_type
            $table->morphs('transaction');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');

            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            $table->decimal('to_pay', 20, 4)->default(0);
            $table->decimal('to_receive', 20, 4)->default(0);

            // auto creates transaction_id & transaction_type
            $table->morphs('transaction');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');

            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            $table->decimal('to_pay', 20, 4)->default(0);
            $table->decimal('to_receive', 20, 4)->default(0);

            // auto creates transaction_id & transaction_type
            $table->morphs('transaction');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');

            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            $table->decimal('to_pay', 20, 4)->default(0);
            $table->decimal('to_receive', 20, 4)->default(0);

            // auto creates transaction_id & transaction_type
            $table->morphs('transaction');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');

            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            $table->decimal('to_pay', 20, 4)->default(0);
            $table->decimal('to_receive', 20, 4)->default(0);

            // auto creates transaction_id & transaction_type
            $table->morphs('transaction');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');

            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            $table->decimal('to_pay', 20, 4)->default(0);
            $table->decimal('to_receive', 20, 4)->default(0);

            // auto creates transaction_id & transaction_type
            $table->morphs('transaction');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');

            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            $table->decimal('to_pay', 20, 4)->default(0);
            $table->decimal('to_receive', 20, 4)->default(0);

            // auto creates transaction_id & transaction_type
            $table->morphs('transaction');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');

            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            $table->decimal('to_pay', 20, 4)->default(0);
            $table->decimal('to_receive', 20, 4)->default(0);

            // auto creates transaction_id & transaction_type
            $table->morphs('transaction');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');

            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            $table->decimal('to_pay', 20, 4)->default(0);
            $table->decimal('to_receive', 20, 4)->default(0);

            // auto creates transaction_id & transaction_type
            $table->morphs('transaction');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');

            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            $table->decimal('to_pay', 20, 4)->default(0);
            $table->decimal('to_receive', 20, 4)->default(0);

            // auto creates transaction_id & transaction_type
            $table->morphs('transaction');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
