metadata    :name        => "SimpleRPC Agent For PUPPI Commands",
            :description => "Agent to query PUPPI commands via MCollective", 
            :author      => "Al @ Lab42",
            :license     => "Apache License 2.0",
            :version     => "0.3",
            :url         => "http://www.example42.com/",
            :timeout     => 600

[ "check" , "log" , "info" ].each do |myact| 
    action myact, :description => "Run puppi myact" do
        display :always

        input :project,
              :prompt      => "Project",
              :description => "PUPPI project",
              :type        => :string,
              :validation  => '^[a-zA-Z\-\.=_\d]+$',
              :optional    => true,
              :maxlength   => 50

        input :puppioptions,
              :prompt      => "Puppi options",
              :description => "PUPPI options",
              :type        => :string,
              :validation  => '^[a-zA-Z\-\.=_\d]+$',
              :optional    => true,
              :maxlength   => 50

        output :data,
              :description => "Output from the Puppi run",
              :display_as  => "Output"

        output :exitcode,
              :description  => "Exit Code from the Puppi run",
              :display_as => "Exit Code"
    end
end

[ "deploy" , "rollback" , "init" , "configure" ].each do |myact|
    action myact, :description => "Run puppi myact" do
        display :always

        input :project,
              :prompt      => "Project",
              :description => "PUPPI project",
              :type        => :string,
              :validation  => '^[a-zA-Z\-\.=_\d]+$',
              :optional    => false,
              :maxlength   => 50

        input :puppioptions,
              :prompt      => "Puppi options",
              :description => "PUPPI options",
              :type        => :string,
              :validation  => '^[a-zA-Z\-\.=_\d]+$',
              :optional    => true,
              :maxlength   => 50

        output :data,
              :description => "Output from the Puppi run",
              :display_as  => "Output"

        output :exitcode,
              :description  => "Exit Code from the Puppi run",
              :display_as => "Exit Code"
    end
end

