require "rubygems"


task :default => [:clear,:build]
$source = 'src'
$dist = 'dist'
$phpunit='phpunit'
$exclude = ['CachedRouter.class.php', 'v.inc.php', 'Lazy.class.php']

task :build do |x, args|
    temp = File.new "#{$dist}/_v.inc.php", 'w'
    temp.write "<?php\n\nnamespace net\\shawn_huang\\pretty;\n"
    files = FileList.new("#{$source}/*").to_ary().map do |f|
        if $exclude.include? File.basename f
            puts "Skip File: #{f}"
            next
        end
        if File.directory? f
            copyFoler f
            next
        end
        if !f.match /(\.class\.php)|(\.interface\.php)$/
            puts "Skip File: #{f}"
            next
        end
        file = File.new f, 'r'
        start = false
        commentStart = false
        file.each do |line|
            if not start
                start = true if line.match /^namespace/
                puts "skip line in #{File.basename f}:#{line}"
                next
            end
            if (not commentStart) and line.match /^\s*\/\*{1,2}/
                commentStart = true
                puts "skip line in #{File.basename f}:#{line}"
                next
            end
            if commentStart and line.match /\s*\*\/\s*$/
                commentStart = false
                puts "skip line in #{File.basename f}:#{line}"
                next
            end
            if commentStart and line.match /^\s*\*/
                puts "skip line in #{File.basename f}:#{line}"
                next
            end
            if ["\r", "\n", ""].include? line.strip()
                puts "skip line in #{File.basename f}: \#\# empty \#\#"
                next
            end
            if line.match /^\s*#|(\/\/)/
                puts "skip line in #{File.basename f}:#{line}"
                next
            end

            temp.puts line
        end
    end
    temp.puts "\n"
    temp.close
    File.rename "#{$dist}/_v.inc.php", "#{$dist}/v.inc.php"
    puts 'finished.'
end

def copyFoler(file, dir = '')
    dir += "/#{File.basename file}"
    FileList.new("#{file}/*").each do |f|
        if File.directory? f
            copyFoler f, dir
            next
        end
        targetFolder = "#{$dist}#{dir}"
        if !File.directory? targetFolder
            puts "Create dir #{targetFolder}"
            FileUtils.mkdir targetFolder
        end
        source = f
        target = "#{targetFolder}/#{File.basename f}"
        puts "Copy #{source} => #{target}"
        FileUtils.cp source, target
    end
end

task :clear do
    FileUtils.rm_rf $dist
    FileUtils.mkdir $dist
end
