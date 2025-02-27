
cd nagiosxi/basedir/html/includes/lang/locale || exit 1
for lang in *; do
  if [ -d "$lang" ]; then
    locale-gen "$lang" &> /dev/null
    locale-gen "$lang.UTF-8" &> /dev/null
  fi
done
echo "Configuring locale... this might take a minute..."
locale-gen &> /dev/null
